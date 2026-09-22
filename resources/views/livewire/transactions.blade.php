<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Sales history</p>
            <h1>Transactions</h1>
            <p class="muted">Review completed POS transactions and reprint receipts.</p>
        </div>
    </div>
    <div class="toolbar"><input wire:model.live.debounce.300ms="search" type="search"
            placeholder="Search by receipt number or payment method..."></div>
    <section class="content-section">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Operator</th>
                        <th>Location</th>
                        <th>Payment</th>
                        <th>Total</th>
                        <th>Recorded</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        <tr>
                            <td><strong>#{{ $sale->id }}</strong></td>
                            <td>{{ $sale->user?->name ?? 'Unknown' }}</td>
                            <td>{{ $sale->location?->name ?? 'Unknown' }}</td>
                            <td>{{ ucfirst($sale->payment_method) }}</td>
                            <td>₱{{ number_format($sale->total_amount, 2) }}</td>
                            <td>{{ $sale->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <button class="button button-small button-secondary" type="button"
                                    wire:click="openPreview({{ $sale->id }})">Preview</button>
                                <a class="button button-small button-danger"
                                    href="{{ route('sales.receipt', $sale) }}">Download Receipt</a>
                            </td>
                    </tr>@empty<tr>
                            <td colspan="7" class="empty">No transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>{{ $sales->links('vendor.pagination.livewire') }}
    </section>
    @if ($this->previewSale)
        <div class="checkout-modal" wire:keydown.escape="closePreview" wire:click.self="closePreview" role="dialog"
            aria-modal="true" aria-labelledby="transaction-preview-title">
            <div class="checkout-dialog">
                <div class="checkout-header">
                    <div>
                        <p class="eyebrow">Transaction preview</p>
                        <h2 id="transaction-preview-title">Receipt #{{ $this->previewSale->id }}</h2>
                    </div>
                    <button class="icon-button checkout-close" type="button" wire:click="closePreview"
                        aria-label="Close transaction preview">&times;</button>
                </div>
                <div class="checkout-summary">
                    <div><span>Operator</span><strong>{{ $this->previewSale->user?->name ?? 'Unknown' }}</strong></div>
                    <div><span>Location</span><strong>{{ $this->previewSale->location?->name ?? 'Unknown' }}</strong>
                    </div>
                    <div>
                        <span>Recorded</span><strong>{{ $this->previewSale->created_at->format('Y-m-d H:i') }}</strong>
                    </div>
                    <div><span>Payment method</span><strong>{{ ucfirst($this->previewSale->payment_method) }}</strong>
                    </div>
                </div>
                <div class="checkout-items">
                    @foreach ($this->previewSale->items as $item)
                        <div class="checkout-item">
                            <div><strong>{{ $item->product?->name ?? 'Product' }}</strong><small>{{ $item->quantity }}
                                    x
                                    ₱{{ number_format($item->unit_price, 2) }}</small></div>
                            <strong>₱{{ number_format($item->total_price, 2) }}</strong>
                        </div>
                    @endforeach
                </div>
                <div class="checkout-summary">
                    <div><span>Subtotal</span><strong>₱{{ number_format($this->previewSale->subtotal, 2) }}</strong>
                    </div>
                    <div>
                        <span>Discount</span><strong>₱{{ number_format($this->previewSale->discount_amount, 2) }}</strong>
                    </div>
                    <div><span>Tax</span><strong>₱{{ number_format($this->previewSale->tax_amount, 2) }}</strong></div>
                    <div class="checkout-grand-total">
                        <span>Total</span><strong>₱{{ number_format($this->previewSale->total_amount, 2) }}</strong>
                    </div>
                    <div><span>Amount
                            paid</span><strong>₱{{ number_format($this->previewSale->amount_paid, 2) }}</strong></div>
                    <div><span>Change</span><strong>₱{{ number_format($this->previewSale->change_amount, 2) }}</strong>
                    </div>
                </div>
                <div class="checkout-actions">
                    <button class="button button-secondary" type="button" wire:click="closePreview">Close</button>
                    <a class="button button-primary" href="{{ route('sales.receipt', $this->previewSale) }}">Receipt
                        PDF</a>
                </div>
            </div>
        </div>
    @endif
</div>
