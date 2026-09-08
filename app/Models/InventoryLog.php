<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'location_id',
        'change_type',
        'quantity_change',
        'reference_id',
        'created_at',
    ];

    protected $casts = [
        'quantity_change' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Get the product that was logged
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the location where change occurred
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
