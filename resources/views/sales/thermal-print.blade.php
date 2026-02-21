<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $sale->invoice_no }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            width: 58mm;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .receipt {
            width: 58mm;
            padding: 1.2mm 0.8mm;
        }

        .brand-header {
            width: 100%;
            margin-bottom: 1mm;
        }

        .brand-meta {
            width: 100%;
            text-align: center;
            padding: 0 0.6mm;
            transform: translateX(8mm);
            font-family: "Courier New", Courier, monospace;
            color: #000;
        }

        .brand-name {
            font-size: 11px;
            font-weight: 900;
            line-height: 1.1;
        }

        .brand-address {
            font-size: 9.2px;
            font-weight: 800;
            line-height: 1.1;
            margin-top: 1px;
        }

        .receipt-text {
            margin: 0;
            white-space: pre;
            font-family: "Courier New", Courier, monospace;
            font-size: 11.4px;
            line-height: 1.18;
            font-weight: 900;
            letter-spacing: 0;
            text-rendering: geometricPrecision;
            width: 100%;
        }

        .no-print {
            margin: 8px auto;
            text-align: center;
            width: 220px;
        }

        .no-print button {
            border: 1px solid #000;
            background: #fff;
            font-size: 11px;
            padding: 4px 8px;
            cursor: pointer;
        }

        .no-print a {
            display: inline-block;
            margin-left: 6px;
            border: 1px solid #000;
            background: #fff;
            color: #000;
            text-decoration: none;
            font-size: 11px;
            padding: 4px 8px;
        }

        @media print {
            @page {
                size: 58mm auto;
                margin: 0;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
<div class="no-print">
    <button type="button" onclick="window.print()">Print</button>
    <a href="{{ route('sales.print.thermal', ['sale' => $sale, 'raw' => 1]) }}">ESC/POS RAW</a>
</div>

<div class="receipt">
    <div class="brand-header">
        <div class="brand-meta">
            <div class="brand-name">{{ $brandName }}</div>
            @foreach ($brandAddressLines as $addressLine)
                <div class="brand-address">{{ $addressLine }}</div>
            @endforeach
        </div>
    </div>

    <pre class="receipt-text">{{ implode("\n", $receiptLines) }}</pre>
</div>
</body>
</html>
