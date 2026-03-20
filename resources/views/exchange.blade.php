<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exchange {{ $exchange->exchange_code }}</title>

    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            color: #111827;
        }

        .page {
            max-width: 900px;
            margin: auto;
            background: #ffffff;
            padding: 24px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }

        /* HEADER */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .header h1 {
            margin: 0;
            font-size: 20px;
            letter-spacing: 0.5px;
        }

        .header .code {
            text-align: right;
            font-size: 11px;
            color: #6b7280;
        }

        /* SELLER / BUYER */
        .party-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 12px;
            margin-bottom: 20px;
        }

        .party-box {
            width: 50%;
            vertical-align: top;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
        }

        .party-box h4 {
            margin: 0 0 6px;
            font-size: 12px;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 4px;
        }

        .party-box strong {
            font-size: 12px;
        }

        /* SECTION TITLE */
        .section-title {
            margin: 20px 0 8px;
            font-size: 13px;
            font-weight: bold;
            color: #111827;
            border-left: 4px solid #993442;
            padding-left: 8px;
        }

        /* TABLE */
        table.items {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 18px;
        }

        table.items thead th {
            background: #f3f4f6;
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #d1d5db;
            font-weight: bold;
        }

        table.items tbody td {
            padding: 7px 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        table.items tbody tr:last-child td {
            border-bottom: none;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* FOOTER NOTE */
        .footer-note {
            margin-top: 20px;
            font-size: 11px;
            color: #6b7280;
            border-top: 1px dashed #d1d5db;
            padding-top: 10px;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .page {
                border: none;
                border-radius: 0;
            }
        }
    </style>
</head>

<body>

<div class="page">

    <!-- HEADER -->
    <div class="header">
        <h1>EXCHANGE</h1>
        <div class="code">
            <div><strong><b>Exchange No</b></strong></div>
            <div>{{ $exchange->exchange_code }}</div>
        </div>
    </div>

    <!-- SELLER / BUYER -->
    <table class="party-table">
        <tr>
            <td class="party-box">
                <h4>Seller</h4>
                <strong>{{ $exchange->warehouse->warehouse_name ?? '' }}</strong><br>
                {{ $exchange->warehouse->city ?? '' }}<br>
                Phone: {{ $exchange->warehouse->owner_number ?? '' }}<br>
                TIN: {{ $exchange->warehouse->tin_no ?? '' }}
            </td>

            <td class="party-box">
                <h4>Buyer</h4>
                <strong>{{ $exchange->customer->name ?? '' }}</strong><br>
                {{ $exchange->customer->street ?? '' }}<br>
                Phone: {{ $exchange->customer->contact_no ?? '' }}<br>
                OSA Code: {{ $exchange->customer->osa_code ?? '' }}
            </td>
        </tr>
    </table>

    <div class="section-title">Invoice Items</div>
    <table class="items">
        <thead>
        <tr>
            <th>#</th>
            <th>Item</th>
            <th>UOM</th>
            <th class="text-center">Qty</th>
            <th class="text-right">Price</th>
            <th class="text-right">Net</th>
            <th class="text-right">VAT</th>
            <th class="text-right">Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($invoiceItems as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->item->name ?? '' }}</td>
                <td>{{ $item->inuoms->name ?? '' }}</td>
                <td class="text-center">{{ $item->item_quantity }}</td>
                <td class="text-right">{{ number_format($item->item_price, 2) }}</td>
                <td class="text-right">{{ number_format($item->net_total, 2) }}</td>
                <td class="text-right">{{ number_format($item->VAT ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($item->total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="section-title">Return Items</div>
    <table class="items">
        <thead>
        <tr>
            <th>#</th>
            <th>Item</th>
            <th>UOM</th>
            <th class="text-center">Qty</th>
            <th class="text-right">Price</th>
            <th class="text-right">Net</th>
            <th class="text-right">VAT</th>
            <th class="text-right">Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($returnItems as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->item->name ?? '' }}</td>
                <td>{{ $item->uoms->name ?? '' }}</td>
                <td class="text-center">{{ $item->item_quantity }}</td>
                <td class="text-right">{{ number_format($item->item_price, 2) }}</td>
                <td class="text-right">{{ number_format($item->net_total, 2) }}</td>
                <td class="text-right">{{ number_format($item->VAT ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($item->total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="footer-note">
        This is a system generated exchange document.
    </div>

</div>

</body>
</html>
