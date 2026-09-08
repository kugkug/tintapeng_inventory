<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'sku' => $this->sku,
            'category_id' => $this->category_id,
            'category' => $this->when($this->relationLoaded('category'), $this->category),
            'barcode' => $this->barcode,
            'barcode_format' => $this->barcode_format,
            'barcode_image_path' => $this->barcode_image_path,
            'cost_per_unit' => (float) $this->cost_per_unit,
            'selling_price' => (float) $this->selling_price,
            'profit_margin_percentage' => $this->getProfitMarginPercentage(),
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
