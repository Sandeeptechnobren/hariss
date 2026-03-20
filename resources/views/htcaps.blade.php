<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Caps Collection #{{ $order->osa_code }}</title>

    <style>
        body {
            font-family: "Inter", Arial, sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 10px;
            font-size: 12px;
            line-height: 1.3;
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
            text-align: right;
            border-bottom: 1px solid #ddd;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }

        .invoice-title h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .invoice-title span {
            font-size: 12px;
        }

        /* TABLE */
        .table-wrapper {
            width: 100%;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        thead th {
            background: #f0f2f5;
            padding: 6px;
            border-bottom: 1px solid #ccc;
            text-align: left;
        }

        tbody td {
            padding: 6px;
            border-bottom: 1px solid #eee;
        }

        /* COLUMN WIDTH FIXES */
        th:nth-child(1) { width: 40px; }
        th:nth-child(2) { width: 110px; }
        th:nth-child(4) { width: 70px; }
        th:nth-child(5) { width: 60px; }
        th:nth-child(6) { width: 90px; }
    </style>
</head>

<body>

<div class="invoice-container">

    <!-- HEADER -->
    <header>
        <div class="invoice-title">
            <h2>Caps Deposit</h2>
            <span>{{ $order->osa_code }}</span>
        </div>
    </header>
    <table width="100%" style="border-collapse: separate; border-spacing: 10px 0; margin-bottom:15px;">
        <tr>
            <td style="
                width:50%; 
                vertical-align:top;
                background:#fafafa;
                padding:12px;
                border:1px solid #ececec;
                border-radius:6px;
                font-size:12px;
            ">
                <h4 style="margin:0 0 5px 0; font-size:12px; font-weight:600;">Seller</h4>
                <strong>{{ $order->warehouse->warehouse_name ?? '' }}</strong><br>
                {{ $order->warehouse->city ?? '' }}<br>
                Phone: {{ $order->warehouse->owner_number ?? '' }}<br>
                OSA CODE: {{ $order->warehouse->warehouse_code ?? '' }}
            </td>

            <td style="
                width:50%; 
                vertical-align:top;
                background:#fafafa;
                padding:12px;
                border:1px solid #ececec;
                border-radius:6px;
                font-size:12px;
            ">
                <h4 style="margin:0 0 5px 0; font-size:12px; font-weight:600;">Driver</h4>
                <strong>{{ $order->driverinfo->osa_code ?? '' }}</strong><br>
                {{ $order->driverinfo->driver_name ?? '' }}<br>
                Phone: {{ $order->driverinfo->contactno ?? '' }}<br>
                Claim Date: {{ $order->driverinfo->claim_date ?? '' }}
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
                    <th>Collected Qty</th>
                    <th>Receive Amount</th>
                    <th>Remarks</th>

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
                    <td>{{ number_format($item->receive_qty, 0) }}</td>
                    <td>{{ number_format($item->receive_amount, 0) }}</td>
                    <td>{{ $item->remarks ?? ''}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

</body>
</html>
