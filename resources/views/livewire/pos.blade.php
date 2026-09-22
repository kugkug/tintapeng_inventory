<div x-data x-init="$nextTick(() => $refs.search?.focus())"
    x-on:keydown.window="if ($event.key === '*' && document.activeElement === $refs.search && !$wire.quantityEntryActive) { $event.preventDefault(); $wire.activateQuantityEntry(); }"
    x-on:pos-focus-search.window="$nextTick(() => $refs.search?.focus())"
    x-effect="if ($wire.quantityEntryActive) { $nextTick(() => $refs.quantity?.focus()) }">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Sales desk</p>
            <h1>Point of sale</h1>
            <p class="muted">Build a basket from the tenant catalog.</p>
        </div>
    </div>
    <div class="pos-layout">
        <section>
            <div class="pos-scan-controls">
                <input class="search-input" x-ref="search" wire:model.live.debounce.250ms="search"
                    wire:keydown.enter.prevent="scanBarcode" type="search"
                    placeholder="Scan barcode or search products...">
                @if ($quantityEntryActive)
                    <input class="quantity-input" x-ref="quantity" wire:model.live="quantityInput"
                        wire:keydown.enter.prevent="confirmQuantity" type="number" min="1" step="1"
                        placeholder="Qty" aria-label="Quantity to add">
                @elseif ($quantityConfigured)
                    <span class="quantity-display" aria-label="Quantity to add">Qty {{ $pendingQuantity }}</span>
                @else
                    <span class="quantity-hint" aria-label="Press asterisk to enter quantity">* qty</span>
                @endif
            </div>
            @error('quantityInput')
                <small class="field-error">{{ $message }}</small>
            @enderror
            <div class="product-grid">
                @forelse($products as $product)
                    <button type="button" class="product-tile"
                        wire:click="addToCart({{ $product->id }})"><strong>{{ $product->name }}</strong><span>₱{{ number_format($product->selling_price, 2) }}</span><small>{{ $product->inventoryItems->sum('quantity') }}
                        in stock</small></button>@empty<div class="empty">No products found.</div>
                @endforelse
            </div>
        </section>
        <aside class="cart-panel">
            <div class="section-heading">
                <h2>Current basket</h2><span class="badge">{{ count($cart) }}</span>
            </div>
            @forelse($cart as $item)
                <div class="cart-line">
                    <div><strong>{{ $item['name'] }}</strong><small>{{ $item['quantity'] }} x
                            ₱{{ number_format($item['price'], 2) }}</small></div><button class="icon-button"
                        wire:click="removeFromCart({{ $item['id'] }})"
                        aria-label="Remove {{ $item['name'] }}">&times;</button>
            </div>@empty<p class="muted">Add a product to begin.</p>
            @endforelse
            <div class="cart-total"><span>Total</span><strong>₱{{ number_format($this->cartTotal, 2) }}</strong></div>
            <label class="pos-payment">Payment method<select wire:model="paymentMethod">
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="online">Online</option>
                </select></label>
            <button class="button button-primary button-wide" type="button" wire:click="openCheckoutModal"
                @disabled(count($cart) === 0)>Complete checkout</button>
        </aside>
    </div>
    @if ($showCheckoutModal)
        <div class="checkout-modal" wire:keydown.escape="closeCheckoutModal" x-on:keydown.enter.prevent="$wire.checkout()"
            role="dialog" aria-modal="true"
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
                            <div><strong>{{ $item['name'] }}</strong><small>{{ $item['quantity'] }} x
                                    ₱{{ number_format($item['price'], 2) }}</small></div>
                            <strong>₱{{ number_format($item['price'] * $item['quantity'], 2) }}</strong>
                        </div>
                    @endforeach
                </div>
                <div class="checkout-summary">
                    <div><span>Subtotal</span><strong>₱{{ number_format($this->cartTotal, 2) }}</strong></div>
                    <div><span>Discount</span><strong>-₱{{ number_format((float) ($discountAmount ?: 0), 2) }}</strong>
                    </div>
                    <div><span>Tax</span><strong>₱0.00</strong></div>
                    <div class="checkout-grand-total">
                        <span>Total</span><strong>₱{{ number_format($this->discountedTotal, 2) }}</strong>
                    </div>
                    <div><span>Payment method</span><strong>{{ ucfirst($paymentMethod) }}</strong></div>
                </div>
                <div class="checkout-payment">
                    <label for="discount-amount">Bulk order discount</label>
                    <div class="money-input">
                        <span>₱</span>
                        <input id="discount-amount" type="number" min="0" step="0.01"
                            wire:model.live="discountAmount" placeholder="0.00" inputmode="decimal">
                    </div>
                    <small class="muted">Optional discount for bulky orders.</small>
                    @error('discountAmount')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                    <label for="amount-paid">{{ $paymentMethod === 'cash' ? 'Amount paid' : 'Amount charged' }}</label>
                    <div class="money-input">
                        <span>₱</span>
                        <input id="amount-paid" type="number" min="0" step="0.01"
                            wire:model.live="amountPaid" placeholder="0.00" inputmode="decimal"
                            @disabled($paymentMethod !== 'cash')>
                    </div>
                    @error('amountPaid')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                    <div class="change-row">
                        <span>Change</span>
                        <strong class="{{ $this->changeAmount > 0 ? 'change-positive' : '' }}">
                            ₱{{ number_format($this->changeAmount, 2) }}
                        </strong>
                    </div>
                </div>
                <div class="checkout-actions">
                    <button class="button button-secondary" type="button" wire:click="closeCheckoutModal">Back to
                        cart</button>
                    <button class="button button-primary" type="button" wire:click="checkout"
                        wire:loading.attr="disabled">Confirm</button>
                    <button class="button button-success" type="button" wire:click="checkout(true)"
                        wire:loading.attr="disabled">Confirm and Download Receipt</button>
                </div>
            </div>
        </div>
    @endif
</div>
