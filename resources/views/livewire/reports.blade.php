<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Performance</p>
            <h1>Reports</h1>
            <p class="muted">Review completed sales for a selected period.</p>
        </div>
        <div class="report-filters">
            <label>
                <span>Report period</span>
                <select wire:model.live="period">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="custom">Customize date</option>
                </select>
            </label>
            @if ($period === 'custom')
                <label>
                    <span>From</span>
                    <input wire:model.live="startDate" type="date">
                </label>
                <label>
                    <span>To</span>
                    <input wire:model.live="endDate" type="date">
                </label>
            @else
                <label>
                    <span>{{ $period === 'daily' ? 'Date' : 'Reference date' }}</span>
                    <input wire:model.live="date" type="date">
                </label>
            @endif
            <label>
                <span>Category</span>
                <select wire:model.live="categoryId">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="button button-secondary" type="button" wire:click="downloadPdf" wire:loading.attr="disabled"
                wire:target="downloadPdf">Generate PDF</button>
        </div>
    </div>
    <div class="metric-grid">
        <div class="metric"><span>Revenue</span><strong>₱{{ number_format($revenue, 2) }}</strong><small>Selected
                period</small></div>
        <div class="metric"><span>Transactions</span><strong>{{ $transactions }}</strong><small>Completed sales</small>
        </div>
        <div class="metric"><span>Average ticket</span><strong>₱{{ number_format($average, 2) }}</strong><small>Per
                transaction</small></div>
    </div>
    <section class="content-section">
        <div class="section-heading">
            <h2>{{ ucfirst($period) }} transactions</h2>
            <p class="muted">{{ $categoryName }}</p>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Sale</th>
                        <th>Item</th>
                        <th>Operator</th>
                        <th>Payment</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Recorded</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        @foreach ($sale->items as $item)
                            <tr>
                                <td>#{{ $sale->id }}</td>
                                <td>{{ $item->product?->name ?? 'Product' }}</td>
                                <td>{{ $sale->user?->name ?? 'Unknown' }}</td>
                                <td>{{ ucfirst($sale->payment_method) }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>₱{{ number_format($item->total_price, 2) }}</td>
                                <td>{{ $sale->created_at->format('H:i') }}</td>
                            </tr>
                        @endforeach
                    @empty<tr>
                            <td colspan="7" class="empty">No sales for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
