<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Get all purchase orders
     * 
     * @route GET /api/v1/purchase-orders
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $status = $request->input('status');

            $query = PurchaseOrder::where('tenant_id', $user->tenant_id)
                ->with('supplier', 'items.product')
                ->orderByDesc('created_at');

            if ($status) {
                $query->where('status', $status);
            }

            $purchaseOrders = $query->paginate(20);

            return response()->json([
                'success' => true,
                'message' => 'Purchase orders retrieved successfully',
                'data' => $purchaseOrders->items(),
                'pagination' => [
                    'total' => $purchaseOrders->total(),
                    'page' => $purchaseOrders->currentPage(),
                    'per_page' => $purchaseOrders->perPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Create purchase order
     * 
     * @route POST /api/v1/purchase-orders
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'expected_delivery_date' => 'required|date|after:today',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $user = auth('api')->user();
            if (!$user->isManager()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $items = $request->input('items');
            $totalAmount = 0;

            foreach ($items as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            $purchaseOrder = PurchaseOrder::create([
                'tenant_id' => $user->tenant_id,
                'supplier_id' => $request->input('supplier_id'),
                'expected_delivery_date' => $request->input('expected_delivery_date'),
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'notes' => $request->input('notes'),
            ]);

            foreach ($items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase order created successfully',
                'data' => [
                    'id' => $purchaseOrder->id,
                    'status' => $purchaseOrder->status,
                    'total_amount' => $purchaseOrder->total_amount,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get purchase order by ID
     * 
     * @route GET /api/v1/purchase-orders/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $purchaseOrder = PurchaseOrder::where('tenant_id', $user->tenant_id)
                ->with('supplier', 'items.product')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Purchase order retrieved successfully',
                'data' => $purchaseOrder,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Update purchase order (status, delivery info)
     * 
     * @route PUT /api/v1/purchase-orders/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string|in:pending,received,cancelled',
            'received_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $user = auth('api')->user();
            if (!$user->isManager()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $purchaseOrder = PurchaseOrder::where('tenant_id', $user->tenant_id)->findOrFail($id);
            $oldStatus = $purchaseOrder->status;
            $newStatus = $request->input('status', $oldStatus);

            $purchaseOrder->update([
                'status' => $newStatus,
                'received_date' => $request->input('received_date'),
                'notes' => $request->input('notes'),
            ]);

            // If status changed to received, update inventory
            if ($oldStatus !== 'received' && $newStatus === 'received') {
                foreach ($purchaseOrder->items as $item) {
                    // Increment stock at default location (location_id = 1, or first location)
                    $this->productService->incrementStock(
                        $item->product_id,
                        $user->tenant_id,
                        $item->quantity,
                        1 // Default to location 1
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase order updated successfully',
                'data' => $purchaseOrder,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete purchase order (admin only)
     * 
     * @route DELETE /api/v1/purchase-orders/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $user = auth('api')->user();
            if (!$user->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $purchaseOrder = PurchaseOrder::where('tenant_id', $user->tenant_id)->findOrFail($id);

            if ($purchaseOrder->status === 'received') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete received purchase orders',
                ], 409);
            }

            $purchaseOrder->delete();

            return response()->json([
                'success' => true,
                'message' => 'Purchase order deleted successfully',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
