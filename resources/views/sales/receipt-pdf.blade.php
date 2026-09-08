<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Receipt #{{ $sale->id }}</title>
    <style>
        @page {
            margin: 12mm;
        }

        body {
            color: #172033;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            margin: 0;
        }

        h1 {
            font-size: 18pt;
            margin: 0 0 4mm;
        }

        .muted {
            color: #667085;
        }

        .header {
            border-bottom: 1px solid #b8c0cc;
            margin-bottom: 6mm;
            padding-bottom: 4mm;
        }

        .meta {
            font-size: 9pt;
            line-height: 1.6;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border-bottom: 1px solid #e4e7ec;
            padding: 3mm 0;
            text-align: left;
        }

        th {
            color: #667085;
            font-size: 8pt;
            text-transform: uppercase;
        }

        td:last-child,
        th:last-child {
            text-align: right;
        }

        .totals {
            margin-top: 5mm;
            width: 45%;
            margin-left: auto;
        }

        .totals td {
            border: 0;
            padding: 1.5mm 0;
        }

        .totals tr:last-child {
            border-top: 2px solid #172033;
            font-size: 12pt;
            font-weight: bold;
        }

        .footer {
            border-top: 1px solid #b8c0cc;
            margin-top: 12mm;
            padding-top: 4mm;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Sales Receipt</h1>
        <div class="meta">Receipt #{{ $sale->id }}<br>Recorded:
            {{ $sale->created_at->format('Y-m-d H:i') }}<br>Payment: {{ ucfirst($sale->payment_method) }}<br>Operator:
            {{ $sale->user?->name ?? 'Unknown' }}</div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Unit price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>{{ $item->product?->name ?? 'Product' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>₱{{ number_format($item->unit_price, 2) }}</td>
                    <td>₱{{ number_format($item->total_price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table class="totals">
        <tr>
            <td>Subtotal</td>
            <td>₱{{ number_format($sale->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td>Total</td>
            <td>₱{{ number_format($sale->total_amount, 2) }}</td>
        </tr>
    </table>
    <div class="footer">Thank you for your purchase.</div>
</body>

</html>
