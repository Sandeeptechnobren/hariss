<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Company Order #{{ $order->order_code }}</title>

    <style>
        @page {
            size: A4;
            margin: 12mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
        }

        .invoice-container {
            width: 100%;
        }

        /* ================= HEADER ================= */
        .header {
            text-align: right;
            border-bottom: 1px solid #999;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }

        .header h2 {
            margin: 0;
            font-size: 18px;
        }

        .header small {
            font-size: 10px;
        }

        /* ================= ADDRESS ================= */
        .address-wrapper {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .address-box {
            width: 50%;
            border: 1px solid #ccc;
            padding: 8px;
            vertical-align: top;
        }

        .address-box h4 {
            margin: 0 0 4px;
            font-size: 11px;
        }

        /* ================= TABLE ================= */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: #f2f2f2;
            border: 1px solid #ccc;
            padding: 5px;
            font-size: 11px;
        }

        tbody td {
            border: 1px solid #ddd;
            padding: 5px;
            font-size: 11px;
        }

        /* Column widths (10 columns) */
        th:nth-child(1), td:nth-child(1) { width: 4%;  text-align: center; }
        th:nth-child(2), td:nth-child(2) { width: 12%; }
        th:nth-child(3), td:nth-child(3) { width: 20%; }
        th:nth-child(4), td:nth-child(4) { width: 6%;  text-align: center; }
        th:nth-child(5), td:nth-child(5) { width: 6%;  text-align: right; }
        th:nth-child(6), td:nth-child(6) { width: 10%; text-align: right; }
        th:nth-child(7), td:nth-child(7) { width: 10%; text-align: right; }
        th:nth-child(8), td:nth-child(8) { width: 10%; text-align: right; }
        th:nth-child(9), td:nth-child(9) { width: 10%; text-align: right; }
        th:nth-child(10), td:nth-child(10){ width: 12%; text-align: right; }

        /* Allow wrapping ONLY for item name */
        td:nth-child(3) {
            white-space: normal;
            word-break: break-word;
        }

        /* ================= TOTALS ================= */
        .totals {
            width: 40%;
            margin-left: auto;
            margin-top: 12px;
            border: 1px solid #ccc;
        }

        .totals td {
            padding: 6px;
            font-size: 11px;
        }

        .totals tr:last-child td {
            font-weight: bold;
            border-top: 2px solid #000;
            font-size: 12px;
        }

        /* ================= FOOTER ================= */
        .note {
            margin-top: 12px;
            padding: 8px;
            border-left: 4px solid #ccc;
            font-size: 11px;
        }

        .payment {
            margin-top: 6px;
            font-weight: bold;
            font-size: 11px;
        }
        .address-wrapper {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .address-box {
            border: 1px solid #ccc;
            padding: 10px;
        }
        table {
            border-collapse: collapse;
        }
    </style>
</head>

<body>
<div class="invoice-container">

    <!-- HEADER -->
    <div class="header">
        <h2>Company Order</h2>
        <small>{{ $order->order_code }}</small>
    </div>

    <!-- ADDRESSES -->
<table style="width:100%; margin-bottom:14px; border-bottom:1px solid #ccc; table-layout:fixed;">
    <tr>
        <!-- SELLER -->
        <td
            style="
                width:50%;
                vertical-align:top;
                text-align:left;
                padding-right:20px;
            "
        >
            <strong>Seller</strong><br><br>

            @if($order->warehouse)
                <strong>{{ $order->warehouse->warehouse_name ?? '' }}</strong><br>
                {{ $order->warehouse->city ?? '' }}<br>
                Phone: {{ $order->warehouse->owner_number ?? '' }}<br>
                Code: {{ $order->warehouse->warehouse_code ?? '' }}
            @else
                &nbsp;<br>&nbsp;<br>&nbsp;
            @endif
        </td>

        <!-- BUYER -->
        <td
            style="
                width:50%;
                vertical-align:top;
                text-align:right;
                padding-left:20px;
            "
        >
            <strong>Buyer</strong><br><br>

            @if($order->customer)
                <strong>{{ $order->customer->business_name ?? '' }}</strong><br>
                {{ $order->customer->town ?? '' }}<br>
                Phone: {{ $order->customer->contact_number ?? '' }}<br>
                OSA Code: {{ $order->customer->osa_code ?? '' }}
            @else
                &nbsp;<br>&nbsp;<br>&nbsp;
            @endif
        </td>
    </tr>
</table>
    <!-- ITEMS TABLE -->
    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Item Code</th>
            <th>Item Name</th>
            <th>UOM</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Excise</th>
            <th>Net</th>
            <th>VAT</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($orderDetails as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->item->code ?? '' }}</td>
                <td>{{ $item->item->name ?? '' }}</td>
                <td>{{ $item->uoms->name ?? '' }}</td>
                <td>{{ number_format($item->quantity, 0) }}</td>
                <td>{{ number_format($item->item_price, 2) }}</td>
                <td>{{ number_format($item->excise, 2) }}</td>
                <td>{{ number_format($item->net, 2) }}</td>
                <td>{{ number_format($item->vat, 2) }}</td>
                <td>{{ number_format($item->total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <!-- TOTALS -->
    <table class="totals">
        <tr>
            <td>Net Total</td>
            <td align="right">{{ $order->currency }} {{ number_format($order->net_amount, 2) }}</td>
        </tr>
        <tr>
            <td>VAT</td>
            <td align="right">{{ $order->currency }} {{ number_format($order->vat, 2) }}</td>
        </tr>
        <tr>
            <td>Total</td>
            <td align="right">{{ $order->currency }} {{ number_format($order->total, 2) }}</td>
        </tr>
    </table>

    <!-- FOOTER -->
    <div class="note">
        <strong>Customer Note:</strong> {{ $order->comment ?? 'No notes added.' }}
    </div>

</div>
</body>
</html>
