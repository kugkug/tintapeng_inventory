<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    protected $fillable = [
        'product_id',
        'location_id',
        'quantity',
        'reserved_quantity',
        'last_updated_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'last_updated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the product associated with this inventory item
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the location of this inventory item
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get available quantity (quantity - reserved)
     */
    public function getAvailableQuantity(): int
    {
        return $this->quantity - $this->reserved_quantity;
    }

    /**
     * Check if stock is below minimum threshold
     */
    public function isLowStock(): bool
    {
        return $this->product && $this->quantity <= $this->product->min_stock;
    }
}
