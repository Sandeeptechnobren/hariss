<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Load</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #ffffff;
            margin: 0;
            padding: 12px;
            font-size: 12px;
            line-height: 1.15;
        }

        .invoice-container {
            max-width: 900px;
            margin: auto;
            border: 1px solid #e6e6e6;
            border-radius: 8px;
            padding: 18px;
        }

        header {
            display: flex;
            justify-content: flex-end;
            border-bottom: 1px solid #ededed;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }

        .invoice-title h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .invoice-title span {
            font-size: 10px;
            color: #555;
        }

        /* ADDRESS */
        .address-table {
            width: 100%;
            border-spacing: 8px;
            margin-bottom: 10px;
        }

        .address-cell {
            width: 50%;
            vertical-align: top;
            background: #fafafa;
            padding: 8px;
            border: 1px solid #eeeeee;
            border-radius: 6px;
            font-size: 11px;
        }

        .address-cell h4 {
            margin: 0 0 4px;
            font-size: 11px;
            font-weight: 600;
        }

        /* ITEM TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            table-layout: fixed;
        }

        th {
            background: #f5f6f8;
            padding: 6px;
            font-weight: 600;
            border-bottom: 1px solid #e1e1e1;
            text-align: left;
        }

        td {
            padding: 5px;
            border-bottom: 1px solid #ececec;
            vertical-align: middle;
        }

        /* COLUMN WIDTHS (BALANCED – NO EXTRA SPACE) */
        .col-sl     { width: 5%;  text-align: center; }
        .col-code   { width: 18%; text-align: left; }
        .col-name   { width: 37%; text-align: left; padding-right: 4px; }
        .col-uom    { width: 15%; text-align: center; padding-left: 4px; }
        .col-qty    { width: 25%; text-align: right; }

        @media print {
            body { background: #fff; }
            .invoice-container { border: none; }
        }
    </style>
</head>

<body>

<div class="invoice-container">

    <header>
        <div class="invoice-title" align="right">
            <h2>Load</h2>
            <span>{{ $order->osa_code }}</span>
        </div>
    </header>

    <!-- ADDRESS -->
    <table class="address-table">
        <tr>
            <td class="address-cell">
                <h4>Distributor</h4>
                <strong>{{ $order->warehouse->warehouse_name }}</strong><br>
                {{ $order->warehouse->city }}<br>
                Phone: {{ $order->warehouse->owner_number }}<br>
                TIN: {{ $order->warehouse->tin_no }}
            </td>

            <td class="address-cell">
                <h4>Salesman</h4>
                <strong>
                    {{ ($order->salesman->name ?? '') . '-' . ($order->salesman->osa_code ?? '') }}
                </strong><br>
                Phone: {{ $order->salesman->contact_no ?? '' }}<br>
                Email: {{ $order->salesman->email ?? '' }}<br>
                Role:
                @if($order->salesman->type == 6)
                    {{ $order->salesman->subtype->name ?? '' }}
                @else
                    {{ $order->salesman->salesmanType->salesman_type_name ?? '' }}
                @endif
            </td>
        </tr>
    </table>

    <!-- ITEMS -->
    <table>
        <thead>
        <tr>
            <th class="col-sl">#</th>
            <th class="col-name">Item</th>
            <th class="col-uom">UOM</th>
            <th class="col-qty">Load Qty</th>
        </tr>
        </thead>

        <tbody>
        @foreach($orderDetails as $i => $item)
            <tr>
                <td class="col-sl">{{ $i + 1 }}</td>
                <td class="col-code">{{ $item->item->erp_code }} - {{ $item->item->name }}</td>
                <td class="col-uom">{{ $item->Uom->name }}</td>
                <td class="col-qty">{{ number_format($item->qty, 0) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

</div>

</body>
</html>
