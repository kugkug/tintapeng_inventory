<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">{{ now()->format('l, F j, Y') }}</p>
            <h1>Good morning, {{ auth()->user()->name }}.</h1>
            <p class="muted">Here is the pulse of your inventory today.</p>
        </div><a class="button button-primary" href="{{ route('pos') }}">Open point of sale</a>
    </div>
    <div class="metric-grid">
        <div class="metric"><span>Today
                revenue</span><strong>₱{{ number_format($todayRevenue, 2) }}</strong><small>{{ $todaySales }}
                transactions</small></div>
        <div class="metric"><span>Products</span><strong>{{ number_format($productCount) }}</strong><small>Across your
                catalog</small></div>
        <div class="metric metric-alert"><span>Low
                stock</span><strong>{{ number_format($lowStockCount) }}</strong><small>Needs attention</small></div>
    </div>
    <section class="content-section">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Activity</p>
                <h2>Recent sales</h2>
            </div><a class="text-link" href="{{ route('reports') }}">View reports</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Sale</th>
                        <th>Operator</th>
                        <th>Payment</th>
                        <th>Total</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSales as $sale)
                        <tr>
                            <td>#{{ $sale->id }}</td>
                            <td>{{ $sale->user?->name ?? 'Unknown' }}</td>
                            <td><span class="badge">{{ ucfirst($sale->payment_method) }}</span></td>
                            <td>₱{{ number_format($sale->total_amount, 2) }}</td>
                            <td>{{ $sale->created_at->diffForHumans() }}</td>
                    </tr>@empty<tr>
                            <td colspan="5" class="empty">No sales recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
