<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

#[Layout('layouts.livewire')]
class PengPos extends Pos
{
    public int $selectedCategoryId = 0;

    public function mount(): void
    {
        $this->quantityInput = '1';
    }

    public function addToCart(int $productId): void
    {
        $this->validate([
            'quantityInput' => ['required', 'integer', 'min:1'],
        ], [
            'quantityInput.required' => 'Enter a quantity before adding a product.',
            'quantityInput.integer' => 'Quantity must be a whole number.',
            'quantityInput.min' => 'Quantity must be at least 1.',
        ]);

        $this->pendingQuantity = (int) $this->quantityInput;
        parent::addToCart($productId);
        $this->quantityInput = '1';
    }

    public function selectCategory(int $categoryId): void
    {
        if ($categoryId === 0) {
            $this->selectedCategoryId = 0;

            return;
        }

        $this->selectedCategoryId = Category::where('tenant_id', Auth::user()->tenant_id)
            ->whereKey($categoryId)
            ->exists() ? $categoryId : 0;
    }

    public function render()
    {
        $tenantId = Auth::user()->tenant_id;
        $productQuery = Product::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->when($this->selectedCategoryId > 0, fn ($query) => $query->where('category_id', $this->selectedCategoryId))
            ->where(fn ($query) => $query->where('name', 'like', "%{$this->search}%")
                ->orWhere('sku', 'like', "%{$this->search}%")
                ->orWhere('barcode', 'like', "%{$this->search}%"))
            ->with('inventoryItems')
            ->orderBy('name')
            ->limit(28);

        return view('livewire.pengpos', [
            'categories' => Category::where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get(),
            'products' => $productQuery->get(),
        ]);
    }
}