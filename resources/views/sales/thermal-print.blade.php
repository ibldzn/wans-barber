<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $sale->invoice_no }} - Thermal 58mm</title>
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
            padding: 2mm 1.5mm;
        }

        .receipt-text {
            margin: 0;
            white-space: pre;
            font-family: "Courier New", Courier, monospace;
            font-size: 13px;
            line-height: 1.2;
            font-weight: 900;
            letter-spacing: 0;
            text-rendering: geometricPrecision;
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
    <pre class="receipt-text">{{ implode("\n", $receiptLines) }}</pre>
</div>
</body>
</html>
