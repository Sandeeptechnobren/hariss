<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Company Delivery #{{ $order->delivery_code }}</title>

    <style>
        body {
            font-family: "Inter", Arial, sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 10px;
            font-size: 12px;
            line-height: 1.2; /* REDUCED LINE SPACING */
        }

        .invoice-container {
            background: #fff;
            max-width: 900px;
            margin: auto;
            border-radius: 8px;
            padding: 20px; /* REDUCED PADDING */
            border: 1px solid #e5e7eb;
        }

        header {
            display: flex;
            justify-content: flex-end;
            border-bottom: 1px solid #ddd;
            padding-bottom: 8px; /* SMALLER */
            margin-bottom: 15px; /* SMALLER */
        }

        .invoice-title h2 {
            margin: 0;
            font-size: 18px; /* SMALLER */
            font-weight: 700;
        }

        .invoice-title span {
            font-size: 10px;
        }
        .address-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px; /* spacing between boxes */
        }

        .address-cell {
            width: 50%;
            vertical-align: top;
            background: #fafafa;
            padding: 10px;
            border: 1px solid #ececec;
            border-radius: 6px;
            font-size: 11px;
            text-align: left; /* IMPORTANT: keeps text left aligned */
        }
        .address-cell h4 {
            margin: 0 0 5px;
            font-size: 11px;
            font-weight: 600;
        }

        /* TABLE */
        .table-wrapper {
            overflow-x: auto;
            border-radius: 6px;
            border: 1px solid #ddd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px; /* SMALLER FONT */
        }

        th {
            background: #f0f2f5;
            padding: 6px; /* REDUCED */
            border-bottom: 1px solid #ccc;
            font-size: 11px;
        }

        td {
            padding: 5px; /* REDUCED */
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        /* TOTALS */
        .totals {
            max-width: 260px; /* REDUCED WIDTH */
            margin-left: auto;
            margin-top: 15px;
        }

        .totals td {
            font-size: 11px;
            padding: 4px 0; /* REDUCED */
        }

        .totals tr:last-child td {
            font-size: 13px;
            font-weight: 600;
            border-top: 1px solid #666;
            padding-top: 6px;
        }

        .note {
            margin-top: 15px;
            font-size: 11px;
            padding: 10px;
            line-height: 1.1;
            border-left: 3px solid #ccc;
        }

        .payment {
            margin-top: 10px;
            font-size: 12px;
            font-weight: bold;
        }

        @media print {
            body { background: #fff; }
            .invoice-container { box-shadow: none; border: none; }
        }
    </style>
</head>

<body>

<div class="invoice-container">

    <header>
        <div class="invoice-title" align="right">
            <h2>Company Delivery</h2>
            <span>{{ $order->delivery_code }}</span>
        </div>
    </header>

<table class="address-table">
    <tr>
        <!-- SELLER (LEFT) -->
        <td class="address-cell">
            <h4>Seller</h4>
            <strong>{{ $order->warehouse->warehouse_name ?? ''}}</strong>
            <br>{{ $order->warehouse->city ?? ''}}<br>
            Phone: {{ $order->warehouse->owner_number ?? ''}}<br>
           OSA CODE: {{ $order->warehouse->warehouse_code ?? ''}}
        </td>

        <td class="address-cell">
            <h4>Buyer</h4>
            <strong>{{ $order->customer->business_name ?? ''}}</strong><br>
            {{ $order->customer->town ?? '' }}<br> 
            Phone: {{ $order->customer->contact_number  ?? ''}}<br>
            OSA Code: {{ $order->customer->osa_code ?? '' }}
        </td>
    </tr>
</table>

    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Item Code</th>
                <th>Item Name</th>
                <th>UOM</th>
                <th>Qty</th>
                <th>Price</th>
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
                    <td>{{ number_format($item->net, 2) }}</td>
                    <td>{{ number_format($item->vat, 2) }}</td>
                    <td>{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <!-- TOTALS -->
    <div class="totals">
        <table>
            <tr>
            <td>Net Total</td>
            <td align="right">
                {{ $order->currency }} {{ number_format($order->net, 2) }}
            </td>
        </tr>
        <tr>
            <td>VAT</td>
            <td align="right">
                {{ $order->currency }} {{ number_format($order->vat, 2) }}
            </td>
        </tr>
        <tr>
            <td><b>Total</b></td>
            <td align="right">
                <b>{{ $order->currency }} {{ number_format($order->total, 2) }}</b>
            </td>
        </tr>
        </table>
    </div>

    <!-- NOTE -->
    <!-- <div class="note">
        <strong>Customer Note:</strong> {{ $order->comment ?? 'No notes added.' }}
    </div> -->

    <!-- PAYMENT -->

</div>

</body>
</html>
