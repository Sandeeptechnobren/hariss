<!-- <!DOCTYPE html>
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
    <!-- <div class="header">
        <h2>Company Order</h2>
        <small>{{ $order->order_code }}</small>
    </div>

<table style="width:100%; margin-bottom:14px; border-bottom:1px solid #ccc; table-layout:fixed;">
    <tr>
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
        <!-- <td
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
    <!-- <table>
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
    </table> -->

    <!-- TOTALS -->
    <!-- <table class="totals">
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
    <!-- <div class="note">
        <strong>Customer Note:</strong> {{ $order->comment ?? 'No notes added.' }}
    </div>

</div>
</body>
</html>  -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Company Order #{{ $order->order_code }}</title>

    <style>
        body {
            font-family: "Inter", Arial, sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 10px;
            font-size: 12px;
            line-height: 1.2; 
        }

        .invoice-container {
            background: #fff;
            max-width: 900px;
            margin: auto;
            border-radius: 8px;
            padding: 20px; 
            border: 1px solid #e5e7eb;
        }

        header {
            display: flex;
            justify-content: flex-end;
            border-bottom: 1px solid #ddd;
            padding-bottom: 8px; 
            margin-bottom: 15px;
        }

        .invoice-title h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .invoice-title span {
            font-size: 10px;
        }
        .address-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px;
        }

        .address-cell {
            width: 50%;
            vertical-align: top;
            background: #fafafa;
            padding: 10px;
            border: 1px solid #ececec;
            border-radius: 6px;
            font-size: 11px;
            text-align: left; 
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
            font-size: 11px; 
        }

        th {
            background: #f0f2f5;
            padding: 6px; 
            border-bottom: 1px solid #ccc;
            font-size: 11px;
        }

        td {
            padding: 5px; 
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .totals {
            max-width: 260px; 
            margin-left: auto;
            margin-top: 15px;
        }

        .totals td {
            font-size: 11px;
            padding: 4px 0; 
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
            <h2>Company Order</h2>
            <span>{{ $order->order_code }}</span>
        </div>
    </header>

    <table class="address-table">
        <tr>
            {{-- SELLER --}}
            <td class="address-cell">
                <h4>Seller</h4>

                @if(!empty($order->warehouse_id) && $order->warehouse)
                    {{-- Seller from Warehouse --}}
                    <strong>
                        {{ $order->warehouse->warehouse_code ? $order->warehouse->warehouse_code . ' - ' : '' }}
                        {{ $order->warehouse->warehouse_name ?? '' }}
                    </strong><br>
                    {{ $order->warehouse->city ?? '' }}<br>
                    Phone: {{ $order->warehouse->owner_number ?? '' }}<br>
                    TIN: {{ $order->warehouse->tin_no ?? '' }}

                @elseif(!empty($order->company_id) && $order->company)
                    {{-- Seller from Company --}}
                    <strong>
                        {{ $order->company->company_code ? $order->company->company_code . ' - ' : '' }}
                        {{ $order->company->company_name ?? '' }}
                    </strong><br>
                    {{ $order->company->city ?? '' }}<br>
                    Tin: {{ $order->company->tin_number ?? '' }}<br>
                    Phone: {{ $order->company->primary_contact ?? '' }}

                @else
                    <em>No seller information</em>
                @endif
            </td>

            {{-- BUYER (UNCHANGED) --}}
            <td class="address-cell">
                <h4>Buyer</h4>
                  <strong>
                        {{ $order->customer->osa_code ? $order->customer->osa_code . ' - ' : '' }}
                        {{ $order->customer->business_name ?? '' }}
                    </strong><br>
                {{ $order->customer->town ?? '' }}<br> 
                Phone: {{ $order->customer->contact_number ?? '' }}<br>
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
    </div>

    <div class="totals">
        <table>
           <tr>
                <td>Net Total</td>
                <td align="right">
                    {{ $order->currency }} {{ number_format($order->net, 2) }}
                </td>
            </tr>

            <tr>
                <td>Vat</td>
                <td align="right">
                    {{ $order->currency }} {{ number_format($order->vat, 2) }}
                </td>
            </tr>

            <tr>
                <td>Excise</td>
                <td align="right">
                    {{ $order->currency }} {{ number_format($order->excise, 2) }}
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
    <div class="note">
        <strong>Customer Note:</strong> {{ $order->comment ?? 'Urgent delivery' }}
    </div>
</div>

</body>
</html>

