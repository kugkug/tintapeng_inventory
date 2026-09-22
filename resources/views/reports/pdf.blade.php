<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Sales Report</title>
    <style>
        @page {
            margin: 16mm;
        }

        body {
            color: #172033;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9pt;
        }

        h1 {
            font-size: 20pt;
            margin: 0 0 2mm;
        }

        .muted {
            color: #667085;
        }

        .header {
            border-bottom: 1px solid #b8c0cc;
            margin-bottom: 7mm;
            padding-bottom: 4mm;
        }

        .summary {
            border-collapse: collapse;
            margin-bottom: 8mm;
            width: 100%;
        }

        .summary td {
            border: 1px solid #e4e7ec;
            padding: 3mm;
            width: 33.33%;
        }

        .summary strong,
        .summary span {
            display: block;
        }

        .summary strong {
            font-size: 14pt;
            margin-top: 1mm;
        }

        table.sales {
            border-collapse: collapse;
            width: 100%;
        }

        .sales th,
        .sales td {
            border-bottom: 1px solid #e4e7ec;
            padding: 2.5mm 1.5mm;
            text-align: left;
        }

        .sales th {
            color: #667085;
            font-size: 7pt;
            text-transform: uppercase;
        }

        .sales th:nth-child(4),
        .sales td:nth-child(4) {
            text-align: right;
        }

        .empty {
            color: #667085;
            padding: 8mm 0;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Sales Report</h1>
        <div class="muted">{{ $period }} | {{ $categoryName }}: {{ $startDate->format('M d, Y') }}@if (!$startDate->isSameDay($endDate))
                - {{ $endDate->format('M d, Y') }}
            @endif
        </div>
    </div>

    <table class="summary">
        <tr>
            <td><span class="muted">Revenue</span><strong>₱{{ number_format($revenue, 2) }}</strong></td>
            <td><span class="muted">Transactions</span><strong>{{ $transactions }}</strong></td>
            <td><span class="muted">Average ticket</span><strong>₱{{ number_format($average, 2) }}</strong></td>
        </tr>
    </table>

    <table class="sales">
        <thead>
            <tr>
                <th>Sale</th>
                <th>Item</th>
                <th>Operator</th>
                <th>Payment</th>
                <th>Qty</th>
                <th>Total</th>
                <th>Recorded</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sales as $sale)
                @foreach ($sale->items as $item)
                    <tr>
                        <td>#{{ $sale->id }}</td>
                        <td>{{ $item->product?->name ?? 'Product' }}</td>
                        <td>{{ $sale->user?->name ?? 'Unknown' }}</td>
                        <td>{{ ucfirst($sale->payment_method) }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>₱{{ number_format($item->total_price, 2) }}</td>
                        <td>{{ $sale->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="7" class="empty">No sales for this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
