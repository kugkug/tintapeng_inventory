<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'tenant_id',
        'category_id',
        'barcode',
        'barcode_format',
        'barcode_image_path',
        'name',
        'sku',
        'unit',
        'cost_per_unit',
        'selling_price',
        'min_stock',
        'is_active',
    ];

    protected $casts = [
        'cost_per_unit' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'min_stock' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the product
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the category of this product
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all inventory items for this product
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    /**
     * Get all inventory adjustments for this product
     */
    public function inventoryAdjustments(): HasMany
    {
        return $this->hasMany(InventoryAdjustment::class);
    }

    /**
     * Get all inventory logs for this product
     */
    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    /**
     * Get all sales items for this product
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get all purchase order items for this product
     */
    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Calculate total stock across all locations
     */
    public function getTotalStock(): int
    {
        return $this->inventoryItems()->sum('quantity');
    }

    /**
     * Get profit margin percentage
     */
    public function getProfitMarginPercentage(): float
    {
        if ($this->cost_per_unit == 0) {
            return 0;
        }
        return (($this->selling_price - $this->cost_per_unit) / $this->cost_per_unit) * 100;
    }
}
