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
                            <td><a class="button button-small button-secondary"
                                    href="{{ route('sales.receipt', $sale) }}">Receipt PDF</a></td>
                    </tr>@empty<tr>
                            <td colspan="7" class="empty">No transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>{{ $sales->links('vendor.pagination.livewire') }}
    </section>
</div>
