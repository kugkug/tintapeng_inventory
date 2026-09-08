<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Product Barcodes</title>
    <style>
        @page {
            margin: 18mm;

            color: #172033;
        }

        .label-grid {
            border-collapse: separate;
            border-spacing: 0 8mm;
            table-layout: fixed;
            width: 100%;
        }

        .label-cell {
            padding-right: 5mm;
            vertical-align: top;
            width: 50%;
        }

        .label-cell:last-child {
            padding-left: 0;
            padding-right: 0;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 18px;
            padding-bottom: 8px;
        }

        .label {
            border: 1px solid #b8c0cc;
            display: inline-block;
            height: 46mm;
            margin: 0 5mm 8mm 0;
            padding: 6mm;
            vertical-align: top;
            width: 72mm;
        }

        .label:nth-child(2n) {
            margin-right: 0;
        }

        .label-title {
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 4mm;
            text-transform: uppercase;
        }

        .barcode {
            height: 18mm;
            margin: 0 auto 5mm;
            max-width: 68mm;
            width: 68mm;
        }

        .barcode img {
            height: 100%;
            object-fit: contain;
            width: 100%;
        }

        .field {
            border-top: 1px solid #e4e7ec;
            font-size: 9pt;
            padding: 2mm 0 0;
        }

        .field+.field {
            margin-top: 2mm;
        }

        .field-label {
            color: #667085;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .field-value {
            display: block;
            margin-top: 1mm;
            word-wrap: break-word;
        }
    </style>
</head>

<body>
    <div class="document-title">Product Barcodes</div>
    <table class="label-grid">
        @foreach ($products->chunk(2) as $row)
            <tr>
                @foreach ($row as $product)
                    <td class="label-cell">
                        <div class="label">
                            <div class="label-title">{{ $product['sku'] }}</div>
                            <div class="barcode"><img src="{{ $product['image'] }}" alt="Barcode"></div>
                            <div class="field"><span class="field-label">Product Name</span><span
                                    class="field-value">{{ $product['name'] }}</span></div>
                        </div>
                    </td>
                @endforeach
                @if ($row->count() === 1)
                    <td class="label-cell"></td>
                @endif
            </tr>
        @endforeach
    </table>
</body>

</html>
