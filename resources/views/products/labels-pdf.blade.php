<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Product Names and Prices</title>
    <style>
        @page {
            size: 40mm 30mm;
            margin: 0;
            color: #172033;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        .label {
            align-items: center;
            box-sizing: border-box;
            border: 1px solid #b8c0cc;
            display: flex;
            flex-direction: column;
            height: 30mm;
            justify-content: center;
            overflow: hidden;
            padding: 1.5mm;
            text-align: center;
            width: 40mm;
            page-break-inside: avoid;
        }

        .label.page-break {
            page-break-before: always;
        }


        .barcode {
            margin-top: 4mm;
            flex: 0 0 8mm;
            height: 10mm;
            margin-bottom: 0.8mm;
            text-align: center;
            width: 100%;
        }

        .barcode img {
            height: 100%;
            max-width: 30mm;
            width: 30mm;
        }

        .barcode-missing {
            color: #667085;
            font-size: 6pt;
            padding-top: 2mm;
        }

        .product-name {
            line-height: 1.15;
            max-height: 7mm;
            font-size: 7pt;
            font-weight: bold;
            overflow: hidden;
            word-wrap: break-word;
            padding-right: 2mm;
        }

        .price {
            line-height: 1.1;
            font-size: 12pt;
            font-weight: bold;
        }
    </style>
</head>

<body>
    @foreach ($products as $product)
        <div class="label page-break">
            <div class="barcode">
                @if ($product['barcode'])
                    <img src="{{ $product['barcode'] }}" alt="Barcode">
                @else
                    <span class="barcode-missing">No barcode generated</span>
                @endif
            </div>
            <div class="product-name">{{ $product['name'] }}</div>
            <div class="price">{{ number_format($product['price'], 2) }}</div>
        </div>
    @endforeach
</body>

</html>
