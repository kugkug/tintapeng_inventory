<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAdjustment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'location_id',
        'quantity_change',
        'reason',
        'notes',
        'adjusted_by',
        'created_at',
    ];

    protected $casts = [
        'quantity_change' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Get the product that was adjusted
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the location where adjustment happened
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the user who made the adjustment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}
