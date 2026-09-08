<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Services\BarcodeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.livewire')]
class Products extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingProductId = null;

    public array $selectedProductIds = [];

    public string $name = '';

    public string $sku = '';

    public string $unit = 'pc';

    public string $totalCost = '';

    public string $costPerUnit = '';

    public string $sellingPrice = '';

    public int $quantity = 1;

    public ?int $categoryId = null;

    public function updatedSearch(): void
    {
        try {
            $this->resetPage();
        } catch (Throwable $exception) {
            $this->logException(__FUNCTION__, $exception);
            throw $exception;
        }
    }

    public function updatedTotalCost(): void
    {
        try {
            $this->calculateCostPerUnit();
        } catch (Throwable $exception) {
            $this->logException(__FUNCTION__, $exception);
            throw $exception;
        }
    }

    public function updatedQuantity(): void
    {
        try {
            $this->calculateCostPerUnit();
        } catch (Throwable $exception) {
            $this->logException(__FUNCTION__, $exception);
            throw $exception;
        }
    }

    public function save(): void
    {
        try {
            abort_unless($this->currentUser()->isManager(), 403);

            $data = $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'sku' => [
                    'nullable',
                    'string',
                    'max:100',
                    'regex:/^[A-Za-z0-9._\-\s]+$/',
                    Rule::unique('products', 'sku')
                        ->where(fn ($query) => $query->where('tenant_id', $this->currentUser()->tenant_id))
                        ->ignore($this->editingProductId),
                ],
                'unit' => ['required', 'string', 'max:30'],
                'totalCost' => ['required', 'numeric', 'min:0'],
                'sellingPrice' => ['required', 'numeric', 'min:0'],
                'quantity' => ['required', 'integer', 'min:1'],
                'categoryId' => ['nullable', 'integer'],
            ]);

            $data['costPerUnit'] = round((float) $data['totalCost'] / $data['quantity'], 2);

            $data['sku'] = $this->uniqueSku($data['sku'] ?? null);

            if ($this->editingProductId) {
                $product = Product::where('tenant_id', $this->currentUser()->tenant_id)
                    ->where('is_active', true)
                    ->findOrFail($this->editingProductId);

                DB::transaction(function () use ($data, $product): void {
                    $product->update([
                        'category_id' => $data['categoryId'],
                        'name' => $data['name'],
                        'sku' => $data['sku'],
                        'unit' => $data['unit'],
                        'cost_per_unit' => $data['costPerUnit'],
                        'selling_price' => $data['sellingPrice'],
                    ]);

                    $this->saveInventoryQuantity($product, $data['quantity']);
                });

                $product->update(app(BarcodeService::class)->generateBarcode($product));

                session()->flash('status', 'Product updated successfully.');
            } else {
                $product = DB::transaction(function () use ($data): Product {
                    $product = Product::create([
                        'tenant_id' => $this->currentUser()->tenant_id,
                        'category_id' => $data['categoryId'],
                        'name' => $data['name'],
                        'sku' => $data['sku'],
                        'unit' => $data['unit'],
                        'cost_per_unit' => $data['costPerUnit'],
                        'selling_price' => $data['sellingPrice'],
                    ]);

                    $this->saveInventoryQuantity($product, $data['quantity']);

                    return $product;
                });

                $product->update(app(BarcodeService::class)->generateBarcode($product));
                session()->flash('status', 'Product created successfully.');
            }

            $this->resetForm();
            $this->showForm = false;
        } catch (Throwable $exception) {
            $this->logException(__FUNCTION__, $exception);
            throw $exception;
        }
    }

    public function edit(int $productId): void
    {
        try {
            abort_unless($this->currentUser()->isManager(), 403);

            $product = Product::where('tenant_id', $this->currentUser()->tenant_id)
                ->where('is_active', true)
                ->findOrFail($productId);

            $this->editingProductId = $product->id;
            $this->name = $product->name;
            $this->sku = $product->sku;
            $this->unit = $product->unit;
            $inventory = $this->inventoryForProduct($product);
            $this->quantity = $inventory?->quantity ?? 0;
            $this->totalCost = number_format((float) $product->cost_per_unit * $this->quantity, 2, '.', '');
            $this->costPerUnit = (string) $product->cost_per_unit;
            $this->sellingPrice = (string) $product->selling_price;
            $this->categoryId = $product->category_id;
            $this->showForm = true;
        } catch (Throwable $exception) {
            $this->logException(__FUNCTION__, $exception, ['product_id' => $productId]);
            throw $exception;
        }
    }

    public function remove(int $productId): void
    {
        try {
            abort_unless($this->currentUser()->isManager(), 403);

            Product::where('tenant_id', $this->currentUser()->tenant_id)
                ->where('is_active', true)
                ->findOrFail($productId)
                ->update(['is_active' => false]);

            session()->flash('status', 'Product hidden from the catalog.');
        } catch (Throwable $exception) {
            $this->logException(__FUNCTION__, $exception, ['product_id' => $productId]);
            throw $exception;
        }
    }

    public function generateBarcode(int $productId): void
    {
        try {
            abort_unless($this->currentUser()->isManager(), 403);

            $product = Product::where('tenant_id', $this->currentUser()->tenant_id)
                ->where('is_active', true)
                ->findOrFail($productId);

            $product->update(app(BarcodeService::class)->regenerateBarcode($product));
            session()->flash('status', "Barcode generated for {$product->name}.");
        } catch (Throwable $exception) {
            $this->logException(__FUNCTION__, $exception, ['product_id' => $productId]);
            throw $exception;
        }
    }

    public function generateSelectedBarcodes(): void
    {
        try {
            abort_unless($this->currentUser()->isManager(), 403);

            $productIds = array_map('intval', $this->selectedProductIds);
            if ($productIds === []) {
                session()->flash('status', 'Select at least one product first.');

                return;
            }

            $results = app(BarcodeService::class)->batchGenerateBarcodes(
                $productIds,
                $this->currentUser()->tenant_id,
            );
            $generated = collect($results)->where('success', true)->count();
            $this->selectedProductIds = [];
            session()->flash('status', "Generated {$generated} barcode(s).");
        } catch (Throwable $exception) {
            $this->logException(__FUNCTION__, $exception, ['product_ids' => $this->selectedProductIds]);
            throw $exception;
        }
    }

    public function downloadBarcode(int $productId): mixed
    {
        try {
            $product = Product::where('tenant_id', $this->currentUser()->tenant_id)
                ->where('is_active', true)
                ->findOrFail($productId);

            abort_unless($product->barcode_image_path && Storage::disk('local')->exists($product->barcode_image_path), 404);

            return redirect()->route('products.barcodes-pdf', ['ids' => [$product->id]]);
        } catch (Throwable $exception) {
            $this->logException(__FUNCTION__, $exception, ['product_id' => $productId]);
            throw $exception;
        }
    }

    public function downloadSelectedBarcodes(): mixed
    {
        try {
            $productIds = array_values(array_filter(array_map('intval', $this->selectedProductIds)));
            if ($productIds === []) {
                session()->flash('status', 'Select at least one barcode first.');

                return null;
            }

            $this->selectedProductIds = [];

            return redirect()->route('products.barcodes-pdf', ['ids' => $productIds]);
        } catch (Throwable $exception) {
            $this->logException(__FUNCTION__, $exception, ['product_ids' => $this->selectedProductIds]);
            throw $exception;
        }
    }

    public function downloadSelectedBarcodeImages(): mixed
    {
        try {
            $productIds = array_values(array_filter(array_map('intval', $this->selectedProductIds)));
            if ($productIds === []) {
                session()->flash('status', 'Select at least one barcode first.');

                return null;
            }

            $this->selectedProductIds = [];

            return redirect()->route('products.barcodes-images', ['ids' => $productIds]);
        } catch (Throwable $exception) {
            $this->logException(__FUNCTION__, $exception, ['product_ids' => $this->selectedProductIds]);
            throw $exception;
        }
    }

    private function resetForm(): void
    {
        try {
            $this->reset(['name', 'sku', 'totalCost', 'costPerUnit', 'sellingPrice', 'categoryId', 'editingProductId']);
            $this->unit = 'pc';
            $this->quantity = 1;
        } catch (Throwable $exception) {
            $this->logException(__FUNCTION__, $exception);
            throw $exception;
        }
    }

    private function calculateCostPerUnit(): void
    {
        try {
            $quantity = (int) $this->quantity;

            $this->costPerUnit = $quantity > 0 && is_numeric($this->totalCost)
                ? number_format((float) $this->totalCost / $quantity, 2, '.', '')
                : '';
        } catch (Throwable $exception) {
            $this->logException(__FUNCTION__, $exception);
            throw $exception;
        }
    }

    private function saveInventoryQuantity(Product $product, int $quantity): void
    {
        try {
            $location = $this->currentUser()->tenant->locations()
                ->where('is_active', true)
                ->orderByDesc('is_main_warehouse')
                ->orderBy('id')
                ->firstOrFail();

            InventoryItem::updateOrCreate(
                ['product_id' => $product->id, 'location_id' => $location->id],
                ['quantity' => $quantity, 'last_updated_at' => now()],
            );
        } catch (Throwable $exception) {
            $this->logException(__FUNCTION__, $exception, ['product_id' => $product->id]);
            throw $exception;
        }
    }

    private function inventoryForProduct(Product $product): ?InventoryItem
    {
        try {
            return $product->inventoryItems()
                ->whereHas('location', fn ($query) => $query->where('is_active', true))
                ->with('location')
                ->get()
                ->sortByDesc(fn (InventoryItem $item): bool => (bool) $item->location?->is_main_warehouse)
                ->first();
        } catch (Throwable $exception) {
            $this->logException(__FUNCTION__, $exception, ['product_id' => $product->id]);
            throw $exception;
        }
    }

    private function uniqueSku(?string $sku): string
    {
        try {
            $tenantId = $this->currentUser()->tenant_id;
            $sku = trim((string) $sku);

            if ($sku !== '') {
                return $sku;
            }

            do {
                $generatedSku = 'SKU-'.$tenantId.'-'.Str::upper(Str::random(8));
            } while (Product::where('tenant_id', $tenantId)->where('sku', $generatedSku)->exists());

            return $generatedSku;
        } catch (Throwable $exception) {
            $this->logException(__FUNCTION__, $exception);
            throw $exception;
        }
    }

    public function render()
    {
        try {
            $tenantId = $this->currentUser()->tenant_id;

            return view('livewire.products', [
                'products' => Product::where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->where(fn ($query) => $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('sku', 'like', "%{$this->search}%")
                        ->orWhere('barcode', 'like', "%{$this->search}%"))
                    ->with('category', 'inventoryItems')
                    ->latest()
                    ->paginate(12),
                'categories' => Category::where('tenant_id', $tenantId)->orderBy('name')->get(),
                'units' => Unit::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get(),
            ]);
        } catch (Throwable $exception) {
            $this->logException(__FUNCTION__, $exception);
            throw $exception;
        }
    }

    private function currentUser(): User
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            return $user;
        } catch (Throwable $exception) {
            $this->logException(__FUNCTION__, $exception);
            throw $exception;
        }
    }

    private function logException(string $method, Throwable $exception, array $context = []): void
    {
        Log::error('Products Livewire method failed.', array_merge([
            'method' => $method,
            'user_id' => Auth::id(),
            'tenant_id' => Auth::user()?->tenant_id,
            'exception' => $exception,
        ], $context));
    }
}