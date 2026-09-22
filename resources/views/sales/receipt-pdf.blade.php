<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Receipt #{{ $sale->id }}</title>
    <style>
        @page {
            size: 80mm;
            margin: 4mm;
        }

        body {
            color: #172033;
            font-family: DejaVu Sans, sans-serif;
            font-size: 7pt;
            margin: 0;
        }

        h1 {
            font-size: 12pt;
            margin: 0 0 1.5mm;
        }

        .muted {
            color: #667085;
        }

        .header {
            border-bottom: 1px solid #b8c0cc;
            margin-bottom: 2.5mm;
            padding-bottom: 2mm;
        }

        .meta {
            font-size: 6.5pt;
            line-height: 1.35;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        tr {
            page-break-inside: avoid;
        }

        html,
        body {
            page-break-after: avoid;
            page-break-before: avoid;
        }

        th,
        td {
            border-bottom: 1px solid #e4e7ec;
            padding: 1.2mm 0;
            text-align: left;
        }

        th {
            color: #667085;
            font-size: 6pt;
            text-transform: uppercase;
        }

        th:nth-child(1),
        td:nth-child(1) {
            width: 48%;
        }

        th:nth-child(2),
        td:nth-child(2) {
            width: 12%;
            text-align: center;
        }

        th:nth-child(3),
        td:nth-child(3) {
            width: 20%;
            text-align: right;
        }

        th:nth-child(4),
        td:nth-child(4) {
            width: 20%;
        }

        td:last-child,
        th:last-child {
            text-align: right;
        }

        .totals {
            margin-top: 2.5mm;
            width: 62%;
            margin-left: auto;
        }

        .totals td {
            border: 0;
            padding: 0.6mm 0;
        }

        .totals tr:nth-child(3) {
            border-top: 2px solid #172033;
            font-size: 9pt;
            font-weight: bold;
        }

        .footer {
            border-top: 1px solid #b8c0cc;
            margin-top: 3mm;
            padding-top: 2mm;
            text-align: center;
            font-size: 6.5pt;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>TintaPeng PrinThings</h1>
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
            <td>Discount</td>
            <td>-₱{{ number_format($sale->discount_amount, 2) }}</td>
        </tr>
        <tr>
            <td>Total</td>
            <td>₱{{ number_format($sale->total_amount, 2) }}</td>
        </tr>
        <tr>
            <td>Amount paid</td>
            <td>₱{{ number_format($sale->amount_paid, 2) }}</td>
        </tr>
        <tr>
            <td>Change</td>
            <td>₱{{ number_format($sale->change_amount, 2) }}</td>
        </tr>
    </table>
    <div class="footer">Thank you for your purchase.</div>
</body>

</html>
