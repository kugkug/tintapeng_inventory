<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Discount;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\ProductService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SaleController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Get all sales with pagination
     * 
     * @route GET /api/v1/sales
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $query = Sale::where('tenant_id', $user->tenant_id)
                ->with('items.product', 'user', 'discount')
                ->orderByDesc('created_at');

            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $sales = $query->paginate(50);

            return response()->json([
                'success' => true,
                'message' => 'Sales retrieved successfully',
                'data' => $sales->items(),
                'pagination' => [
                    'total' => $sales->total(),
                    'page' => $sales->currentPage(),
                    'per_page' => $sales->perPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Create a new sale (POS transaction)
     * 
     * @route POST /api/v1/sales
     * @body {
     *   "items": [{"product_id": 1, "quantity": 2, "location_id": 1}, ...],
     *   "discount_code": "SUMMER20",
     *   "payment_method": "cash|card|check",
     *   "notes": "optional"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.location_id' => 'required|exists:locations,id',
            'discount_code' => 'nullable|string|max:50',
            'payment_method' => 'required|string|in:cash,card,check,online',
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
            $items = $request->input('items');

            // Validate all items and calculate total
            $totalAmount = 0;
            $saleItems = [];

            foreach ($items as $item) {
                $product = $this->productService->getProductWithStock($item['product_id'], $user->tenant_id);
                $availableStock = 0;

                // Find stock in specified location
                foreach ($product['inventory_items'] as $inv) {
                    if ($inv['location_id'] == $item['location_id']) {
                        $availableStock = $inv['quantity'];
                        break;
                    }
                }

                if ($availableStock < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for product ID {$item['product_id']}",
                    ], 400);
                }

                $itemTotal = $product['product']['selling_price'] * $item['quantity'];
                $totalAmount += $itemTotal;

                $saleItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $product['product']['selling_price'],
                    'total_price' => $itemTotal,
                    'location_id' => $item['location_id'],
                ];
            }

            // Handle discount
            $discountAmount = 0;
            $discountId = null;

            if ($request->input('discount_code')) {
                $discount = Discount::where('tenant_id', $user->tenant_id)
                    ->where('code', $request->input('discount_code'))
                    ->where('is_active', true)
                    ->first();

                if ($discount && $discount->isValid()) {
                    $discountId = $discount->id;
                    $discountAmount = $discount->discount_type === 'percentage'
                        ? ($totalAmount * $discount->discount_value / 100)
                        : $discount->discount_value;
                }
            }

            $finalTotal = max(0, $totalAmount - $discountAmount);

            // Create sale
            $sale = Sale::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'discount_id' => $discountId,
                'subtotal' => $totalAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $finalTotal,
                'payment_method' => $request->input('payment_method'),
                'notes' => $request->input('notes'),
                'status' => 'completed',
            ]);

            // Create sale items and decrement stock
            foreach ($saleItems as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                ]);

                // Decrement stock
                $this->productService->decrementStock(
                    $item['product_id'],
                    $user->tenant_id,
                    $item['quantity'],
                    $item['location_id']
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale completed successfully',
                'data' => [
                    'sale_id' => $sale->id,
                    'subtotal' => $sale->subtotal,
                    'discount_amount' => $sale->discount_amount,
                    'total_amount' => $sale->total_amount,
                    'payment_method' => $sale->payment_method,
                    'created_at' => $sale->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get sale by ID
     * 
     * @route GET /api/v1/sales/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $sale = Sale::where('tenant_id', $user->tenant_id)
                ->with('items.product', 'user', 'discount')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Sale retrieved successfully',
                'data' => $sale,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Sale not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Update sale (only notes, payment_method for completed sales)
     * 
     * @route PUT /api/v1/sales/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'sometimes|string|in:cash,card,check,online',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = auth('api')->user();
            if (!$user->isManager()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $sale = Sale::where('tenant_id', $user->tenant_id)->findOrFail($id);
            $sale->update($request->only('payment_method', 'notes'));

            return response()->json([
                'success' => true,
                'message' => 'Sale updated successfully',
                'data' => $sale,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Sale not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete sale (only by admin)
     * 
     * @route DELETE /api/v1/sales/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $user = auth('api')->user();
            if (!$user->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $sale = Sale::where('tenant_id', $user->tenant_id)->findOrFail($id);
            $sale->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sale deleted successfully',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Sale not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
