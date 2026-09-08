<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    protected BarcodeService $barcodeService;
    protected CacheService $cacheService;

    public function __construct(BarcodeService $barcodeService, CacheService $cacheService)
    {
        $this->barcodeService = $barcodeService;
        $this->cacheService = $cacheService;
    }

    /**
     * Search products with caching
     * Cached for 30 minutes
     */
    public function searchProducts(int $tenantId, string $query = '', int $page = 1, int $perPage = 20): array
    {
        $cacheKey = $this->cacheService->getCacheKeyForSearch($tenantId, $query, $page);
        
        $cached = $this->cacheService->get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $products = Product::where('tenant_id', $tenantId)
            ->where(function ($q) use ($query) {
                if ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('barcode', 'like', "%{$query}%")
                      ->orWhere('sku', 'like', "%{$query}%");
                }
            })
            ->with('category', 'inventoryItems')
            ->paginate($perPage, ['*'], 'page', $page);

        $result = [
            'items' => $products->items(),
            'total' => $products->total(),
            'page' => $page,
            'per_page' => $perPage,
        ];

        $this->cacheService->put($cacheKey, $result, 30);
        return $result;
    }

    /**
     * Get product with stock information
     */
    public function getProductWithStock(int $productId, int $tenantId): ?array
    {
        $product = Product::where('tenant_id', $tenantId)->findOrFail($productId);

        return [
            'product' => $product,
            'total_stock' => $product->getTotalStock(),
            'inventory_items' => $product->inventoryItems()->with('location')->get(),
            'profit_margin' => $product->getProfitMarginPercentage(),
        ];
    }

    /**
     * Decrement product stock
     */
    public function decrementStock(int $productId, int $tenantId, int $quantity, int $locationId): bool
    {
        $product = Product::where('tenant_id', $tenantId)->findOrFail($productId);
        
        $inventory = $product->inventoryItems()
            ->where('location_id', $locationId)
            ->first();

        if (!$inventory || $inventory->quantity < $quantity) {
            return false;
        }

        $inventory->decrement('quantity', $quantity);
        
        // Invalidate cache
        $this->cacheService->invalidateProductCache($tenantId, $productId);
        
        return true;
    }

    /**
     * Increment product stock
     */
    public function incrementStock(int $productId, int $tenantId, int $quantity, int $locationId): bool
    {
        $product = Product::where('tenant_id', $tenantId)->findOrFail($productId);
        
        $inventory = $product->inventoryItems()
            ->where('location_id', $locationId)
            ->first();

        if (!$inventory) {
            // Create inventory item if doesn't exist
            $inventory = $product->inventoryItems()->create([
                'location_id' => $locationId,
                'quantity' => $quantity,
            ]);
        } else {
            $inventory->increment('quantity', $quantity);
        }
        
        // Invalidate cache
        $this->cacheService->invalidateProductCache($tenantId, $productId);
        
        return true;
    }

    /**
     * Get products by category
     */
    public function getProductsByCategory(int $tenantId, int $categoryId): array
    {
        return Product::where('tenant_id', $tenantId)
            ->where('category_id', $categoryId)
            ->with('inventoryItems')
            ->get()
            ->toArray();
    }

    /**
     * Get low stock products
     */
    public function getLowStockProducts(int $tenantId, int $threshold = 10): array
    {
        $cacheKey = "low_stock:tenant_{$tenantId}";
        
        $cached = $this->cacheService->get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $products = Product::where('tenant_id', $tenantId)
            ->whereHas('inventoryItems', function ($query) use ($threshold) {
                $query->whereRaw('quantity <= ?', [$threshold]);
            })
            ->with('inventoryItems.location')
            ->get();

        $result = $products->toArray();
        $this->cacheService->put($cacheKey, $result, 10); // Cache for 10 minutes

        return $result;
    }

    /**
     * Get product statistics
     */
    public function getProductStats(int $tenantId): array
    {
        return [
            'total_products' => Product::where('tenant_id', $tenantId)->count(),
            'total_stock' => Product::where('tenant_id', $tenantId)
                ->join('inventory_items', 'products.id', '=', 'inventory_items.product_id')
                ->sum('inventory_items.quantity'),
            'total_value' => Product::where('tenant_id', $tenantId)
                ->join('inventory_items', 'products.id', '=', 'inventory_items.product_id')
                ->selectRaw('SUM(products.cost_per_unit * inventory_items.quantity) as total')
                ->value('total'),
        ];
    }
}
