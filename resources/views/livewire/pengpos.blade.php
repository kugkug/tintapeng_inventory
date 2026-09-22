<div class="pengpos-page" x-data x-init="$nextTick(() => $refs.search?.focus())"
    x-on:pos-focus-search.window="$nextTick(() => $refs.search?.focus())">
    <header class="pengpos-header">
        <div class="pengpos-brand"><span class="pengpos-cart-icon"
                aria-hidden="true">&#128722;</span><span><strong>PengPOS</strong><small>Better Choices. Brighter
                    Days.</small></span></div>
        <div class="pengpos-search-wrap">
            <span aria-hidden="true">&#9906;</span>
            <input x-ref="search" wire:model.live.debounce.250ms="search" wire:keydown.enter.prevent="scanBarcode"
                type="search" placeholder="Search products or scan barcode..." aria-label="Search products">
            <input class="pengpos-quantity" x-ref="quantity" wire:model.live="quantityInput" type="number"
                min="1" step="1" placeholder="Qty" aria-label="Quantity to add">
        </div>
        <div class="pengpos-clock">
            <strong>{{ now()->format('g:i A') }}</strong><small>{{ now()->format('M d, Y') }}</small>
        </div>
        <div class="pengpos-cashier"><span
                aria-hidden="true">&#128100;</span><span><strong>{{ auth()->user()->name }}</strong><small>Cashier</small></span>
        </div>
    </header>

    <div class="pengpos-workspace">
        <nav class="pengpos-categories" aria-label="Product categories">
            <button class="pengpos-category {{ $selectedCategoryId === 0 ? 'is-active' : '' }}" type="button"
                wire:click="selectCategory(0)">All Items</button>
            @foreach ($categories as $category)
                <button class="pengpos-category {{ $selectedCategoryId === $category->id ? 'is-active' : '' }}"
                    type="button" wire:click="selectCategory({{ $category->id }})">{{ $category->name }}</button>
            @endforeach
        </nav>

        <section class="pengpos-products" aria-label="Products">
            @error('quantityInput')
                <p class="pengpos-error">{{ $message }}</p>
            @enderror
            <div class="pengpos-product-grid">
                @forelse ($products as $product)
                    <button class="pengpos-product" type="button" wire:click="addToCart({{ $product->id }})">
                        <strong>{{ $product->name }}</strong>
                        <small>{{ $product->inventoryItems->sum('quantity') }} in stock</small>
                        <b>&#8369; {{ number_format($product->selling_price, 2) }}</b>
                    </button>
                @empty
                    <div class="pengpos-empty">No products match your search.</div>
                @endforelse
            </div>

        </section>

        <aside class="pengpos-sale">
            <div class="pengpos-sale-heading">
                <h2>Current Sale</h2><button type="button" wire:click="$set('cart', [])">&#128465; Clear</button>
            </div>
            <div class="pengpos-sale-labels"><span>Item</span><span>Qty</span><span>Price</span><span>Total</span>
            </div>
            <div class="pengpos-sale-items">
                @forelse ($cart as $item)
                    <div class="pengpos-sale-item">
                        <span>{{ $item['name'] }}</span><span>{{ $item['quantity'] }}</span><span>&#8369;
                            {{ number_format($item['price'], 2) }}</span><span>&#8369;
                            {{ number_format($item['price'] * $item['quantity'], 2) }}</span><button type="button"
                            wire:click="removeFromCart({{ $item['id'] }})"
                            aria-label="Remove {{ $item['name'] }}">&times;</button>
                    </div>
                @empty
                    <p class="pengpos-sale-empty">Add a product to begin.</p>
                @endforelse
            </div>
            <div class="pengpos-totals">
                <div><span>Subtotal</span><strong>&#8369; {{ number_format($this->cartTotal, 2) }}</strong></div>
                <div><span>Tax (0%)</span><strong>&#8369; 0.00</strong></div>
                <div class="pengpos-grand-total"><span>Total</span><strong>&#8369;
                        {{ number_format($this->cartTotal, 2) }}</strong></div>
            </div>
            <div class="pengpos-payment-actions">
                <button type="button" class="pengpos-cancel" wire:click="$set('cart', [])"><span>&#10005;</span>
                    Cancel</button>
                <button type="button" class="pengpos-pay" wire:click="openCheckoutModal"
                    @disabled(count($cart) === 0)><span>&#128179;</span> Payment</button>
            </div>
        </aside>
    </div>

    @if ($showCheckoutModal)
        <div class="checkout-modal" wire:keydown.escape="closeCheckoutModal"
            x-on:keydown.enter.prevent="$wire.checkout()" role="dialog" aria-modal="true"
            aria-labelledby="checkout-title">
            <div class="checkout-dialog">
                <div class="checkout-header">
                    <div>
                        <p class="eyebrow">Review transaction</p>
                        <h2 id="checkout-title">Confirm checkout</h2>
                    </div>
                    <button class="icon-button checkout-close" type="button" wire:click="closeCheckoutModal"
                        aria-label="Close checkout review">&times;</button>
                </div>
                <div class="checkout-items">
                    @foreach ($cart as $item)
                        <div class="checkout-item">
                            <div><strong>{{ $item['name'] }}</strong><small>{{ $item['quantity'] }} x &#8369;
                                    {{ number_format($item['price'], 2) }}</small></div>
                            <strong>&#8369; {{ number_format($item['price'] * $item['quantity'], 2) }}</strong>
                        </div>
                    @endforeach
                </div>
                <div class="checkout-summary">
                    <div><span>Subtotal</span><strong>&#8369; {{ number_format($this->cartTotal, 2) }}</strong></div>
                    <div><span>Discount</span><strong>-&#8369;
                            {{ number_format((float) ($discountAmount ?: 0), 2) }}</strong></div>
                    <div class="checkout-grand-total"><span>Total</span><strong>&#8369;
                            {{ number_format($this->discountedTotal, 2) }}</strong></div>
                </div>
                <div class="checkout-payment">
                    <label for="pengpos-amount-paid">Amount paid</label>
                    <div class="money-input"><span>&#8369;</span><input id="pengpos-amount-paid" type="number"
                            min="0" step="0.01" wire:model.live="amountPaid" placeholder="0.00"
                            inputmode="decimal"></div>
                    @error('amountPaid')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                    <div class="change-row"><span>Change</span><strong>&#8369;
                            {{ number_format($this->changeAmount, 2) }}</strong></div>
                </div>
                <div class="checkout-actions">
                    <button class="button button-secondary" type="button" wire:click="closeCheckoutModal">Back to
                        cart</button>
                    <button class="button button-primary" type="button" wire:click="checkout"
                        wire:loading.attr="disabled">Confirm payment</button>
                </div>
            </div>
        </div>
    @endif
</div>
