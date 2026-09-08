<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Catalog</p>
            <h1>Products</h1>
            <p class="muted">Search and maintain the products in this tenant.</p>
        </div>
        @if (auth()->user()->isManager())
            <button class="button button-primary"
                wire:click="$toggle('showForm')">{{ $showForm ? 'Close form' : 'Add product' }}</button>
        @endif
    </div>
    @if ($showForm)
        <section class="form-panel">
            <div class="section-heading">
                <h2>{{ $editingProductId ? 'Edit product' : 'New product' }}</h2><span class="form-note">Costs are
                    calculated from total cost and quantity.</span>
            </div>
            <form wire:submit="save" class="product-form">
                <label>
                    Name
                    <input wire:model="name" type="text" placeholder="Product name">
                    @error('name')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </label>
                <label>SKU<input wire:model="sku" type="text" placeholder="Optional, e.g. COFFEE-001">
                    @error('sku')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </label>
                <label>Unit<select wire:model="unit">
                        <option value="">Select unit</option>
                        @if ($unit && !$units->contains('name', $unit))
                            <option value="{{ $unit }}">{{ $unit }} (current)</option>
                            @endif @foreach ($units as $availableUnit)
                                <option value="{{ $availableUnit->name }}">
                                    {{ $availableUnit->name }}{{ $availableUnit->symbol ? ' (' . $availableUnit->symbol . ')' : '' }}
                                </option>
                            @endforeach
                    </select></label><label>Category<select wire:model="categoryId">
                        <option value="">Uncategorised</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Quantity
                    <input wire:model.live="quantity" type="number" min="1" placeholder="1">
                </label>
                <label>
                    Total cost<input wire:model.live="totalCost" type="number" min="0" step="0.01"
                        placeholder="0.00">
                </label>
                <label>Cost per unit<input value="{{ $costPerUnit }}" type="number" step="0.01"
                        placeholder="Automatically calculated" readonly></label>

                <label>Selling price<input wire:model="sellingPrice" type="number" step="0.01"
                        placeholder="0.00"></label><button class="button button-primary"
                    type="submit">{{ $editingProductId ? 'Save changes' : 'Create product' }}</button>
            </form>
        </section>
    @endif
    <div class="toolbar">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search by name, SKU, or barcode...">
    </div>
    <div class="product-actions"><span>{{ count($selectedProductIds) }} selected</span><button
            class="button button-small button-secondary" wire:click="downloadSelectedBarcodes"
            @disabled(count($selectedProductIds) === 0)>Download selected PDF</button>
        <button class="button button-small button-secondary" wire:click="downloadSelectedBarcodeImages"
            @disabled(count($selectedProductIds) === 0)>Download barcode images</button>
        @if (auth()->user()->isManager())
            <button class="button button-small" wire:click="generateSelectedBarcodes"
                @disabled(count($selectedProductIds) === 0)>Generate selected barcodes</button>
        @endif
    </div>
    <section class="content-section">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th></th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Price</th>
                        @if (auth()->user()->isManager())
                            <th>Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td><input class="product-check" wire:model.live="selectedProductIds"
                                    value="{{ $product->id }}" type="checkbox"
                                    aria-label="Select {{ $product->name }}"></td>
                            <td><strong>{{ $product->name }}</strong><small>{{ $product->unit }}</small></td>
                            <td class="mono">{{ $product->sku }}</td>
                            <td>{{ $product->category?->name ?? 'Uncategorised' }}</td>
                            <td>{{ $product->inventoryItems->sum('quantity') }}</td>
                            <td>₱{{ number_format($product->selling_price, 2) }}</td>
                            @if (auth()->user()->isManager())
                                <td class="table-actions"><button class="button button-small"
                                        wire:click="edit({{ $product->id }})">Edit</button><button
                                        class="button button-small button-secondary"
                                        wire:click="generateBarcode({{ $product->id }})">{{ $product->barcode ? 'Regenerate' : 'Generate' }}</button><button
                                        class="button button-small button-danger"
                                        wire:click="remove({{ $product->id }})"
                                        wire:confirm="Hide {{ $product->name }} from the catalog?">Remove</button></td>
                            @endif
                        </tr>
                    @empty<tr>
                            <td colspan="{{ auth()->user()->isManager() ? 8 : 5 }}" class="empty">No products match
                                this search.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>{{ $products->links('vendor.pagination.livewire') }}
    </section>
</div>
