<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryAdjustment;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Get inventory summary
     * 
     * @route GET /api/v1/inventory
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $locationId = $request->input('location_id');
            $expired = $request->boolean('expired');
            $expiringWithinDays = $request->has('expiring_within_days')
                ? $request->integer('expiring_within_days')
                : null;

            $query = DB::table('inventory_items')
                ->join('products', 'inventory_items.product_id', '=', 'products.id')
                ->join('locations', 'inventory_items.location_id', '=', 'locations.id')
                ->where('products.tenant_id', $user->tenant_id)
                ->select('inventory_items.*', 'products.name as product_name', 'products.barcode', 'locations.name as location_name');

            if ($locationId) {
                $query->where('inventory_items.location_id', $locationId);
            }

            if ($expired) {
                $query->whereNotNull('inventory_items.expiration_date')
                    ->whereDate('inventory_items.expiration_date', '<', today());
            } elseif ($expiringWithinDays !== null && $expiringWithinDays >= 0) {
                $query->whereNotNull('inventory_items.expiration_date')
                    ->whereBetween('inventory_items.expiration_date', [
                        today(),
                        today()->addDays(min($expiringWithinDays, 3650)),
                    ]);
            }

            $inventory = $query->paginate(50);

            return response()->json([
                'success' => true,
                'message' => 'Inventory retrieved successfully',
                'data' => $inventory->items(),
                'pagination' => [
                    'total' => $inventory->total(),
                    'page' => $inventory->currentPage(),
                    'per_page' => $inventory->perPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Adjust inventory (add/remove stock)
     * 
     * @route POST /api/v1/inventory/adjust
     */
    public function adjust(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'location_id' => 'required|exists:locations,id',
            'quantity' => 'required|integer|not_in:0',
            'reason' => 'required|string|in:purchase,sale,adjustment,damage,return,counting',
            'notes' => 'nullable|string',
            'expiration_date' => 'sometimes|nullable|date',
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

            // Check authorization
            if (!$user->isManager() && $request->input('reason') !== 'sale') {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $product = Product::where('tenant_id', $user->tenant_id)->findOrFail($request->input('product_id'));

            // For sales, use decrementStock; for others, use incrementStock
            $quantity = $request->input('quantity');
            $reason = $request->input('reason');

            if ($reason === 'sale') {
                $success = $this->productService->decrementStock(
                    $product->id,
                    $user->tenant_id,
                    abs($quantity),
                    $request->input('location_id')
                );
            } else {
                if ($quantity < 0) {
                    $success = $this->productService->decrementStock(
                        $product->id,
                        $user->tenant_id,
                        abs($quantity),
                        $request->input('location_id')
                    );
                } else {
                    $success = $this->productService->incrementStock(
                        $product->id,
                        $user->tenant_id,
                        $quantity,
                        $request->input('location_id'),
                        $request->input('expiration_date'),
                        $request->has('expiration_date'),
                    );
                }
            }

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock or inventory item not found',
                ], 400);
            }

            // Log adjustment
            InventoryAdjustment::create([
                'product_id' => $product->id,
                'location_id' => $request->input('location_id'),
                'quantity_change' => $quantity,
                'reason' => $reason,
                'user_id' => $user->id,
                'notes' => $request->input('notes'),
                'tenant_id' => $user->tenant_id,
            ]);

            // Log history
            InventoryLog::create([
                'product_id' => $product->id,
                'quantity_before' => 0, // Would need to track previous value
                'quantity_after' => 0,  // Would need to track current value
                'action' => $reason,
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inventory adjusted successfully',
                'data' => [
                    'product_id' => $product->id,
                    'adjustment' => $quantity,
                    'reason' => $reason,
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Product or location not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get low stock alerts
     * 
     * @route GET /api/v1/inventory/low-stock
     */
    public function lowStock(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $threshold = $request->input('threshold', 10);

            $products = $this->productService->getLowStockProducts($user->tenant_id, $threshold);

            return response()->json([
                'success' => true,
                'message' => 'Low stock products retrieved',
                'data' => $products,
                'threshold' => $threshold,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get inventory logs for product
     * 
     * @route GET /api/v1/inventory/logs/{product_id}
     */
    public function logs(Request $request, string $productId): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $product = Product::where('tenant_id', $user->tenant_id)->findOrFail($productId);

            $logs = InventoryLog::where('product_id', $product->id)
                ->with('user')
                ->orderByDesc('created_at')
                ->paginate(50);

            return response()->json([
                'success' => true,
                'message' => 'Inventory logs retrieved',
                'data' => $logs->items(),
                'pagination' => [
                    'total' => $logs->total(),
                    'page' => $logs->currentPage(),
                    'per_page' => $logs->perPage(),
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
