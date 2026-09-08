<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Performance</p>
            <h1>Reports</h1>
            <p class="muted">Review completed sales for a selected day.</p>
        </div><input wire:model.live="date" type="date">
    </div>
    <div class="metric-grid">
        <div class="metric"><span>Revenue</span><strong>₱{{ number_format($revenue, 2) }}</strong><small>Selected
                day</small></div>
        <div class="metric"><span>Transactions</span><strong>{{ $transactions }}</strong><small>Completed sales</small>
        </div>
        <div class="metric"><span>Average ticket</span><strong>₱{{ number_format($average, 2) }}</strong><small>Per
                transaction</small></div>
    </div>
    <section class="content-section">
        <div class="section-heading">
            <h2>Daily transactions</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Sale</th>
                        <th>Operator</th>
                        <th>Payment</th>
                        <th>Total</th>
                        <th>Recorded</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        <tr>
                            <td>#{{ $sale->id }}</td>
                            <td>{{ $sale->user?->name ?? 'Unknown' }}</td>
                            <td>{{ ucfirst($sale->payment_method) }}</td>
                            <td>₱{{ number_format($sale->total_amount, 2) }}</td>
                            <td>{{ $sale->created_at->format('H:i') }}</td>
                    </tr>@empty<tr>
                            <td colspan="5" class="empty">No sales for this date.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
