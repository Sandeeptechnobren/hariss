<!-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Unload</title>

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
            <h2>Unload</h2>
            <span>{{ $order->osa_code }}</span>
        </div>
    </header>

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
                <h4>Salesteam</h4>
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
    <table>
        <thead>
        <tr>
            <th class="col-sl">#</th>
            <th class="col-code">Item</th>
            <th class="col-uom">UOM</th>
            <th class="col-qty">UnLoad Qty</th>
        </tr>
        </thead>

        <tbody>
        @foreach($orderDetails as $i => $item)
            <tr>
                <td class="col-sl">{{ $i + 1 }}</td>
                <td class="col-code">{{ $item->item->erp_code }} - {{ $item->item->name }}</td>
                <td class="col-uom">{{ $item->uoms->name }}</td>
                <td class="col-qty">{{ number_format($item->qty, 0) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

</div>

</body>
</html> -->




<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Sales Unload #{{ $order->osa_code }}</title>
</head>

<body style="font-family: Arial, Helvetica, sans-serif; font-size:14px;">

    <table width="100%" cellpadding="8" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <td style="border:1px solid #000; text-align:center; color:red; font-size:22px; font-weight:bold;">
               Sales Unload
                <div style="font-size:14px; margin-top:4px; color:black;">
                    {{ $order->osa_code }}
                </div>
            </td>
        </tr>
    </table>

     <!-- SELLER -->
    <table width="100%" cellpadding="2" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <th colspan="2" style="border:1px solid #000; text-align:center;">
                Overview
            </th>
        </tr>
        <tr>
            <td style="border:1px solid #000; width:30%;">Unload Date:</td>
            <td style="border:1px solid #000;">{{ $order->unload_date }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Sync Date:</td>
            <td style="border:1px solid #000;">{{ $order->sync_date }}</td>
        </tr>
        
    </table>

    <!-- SELLER -->
    <table width="100%" cellpadding="2" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <th colspan="2" style="border:1px solid #000; text-align:center;">
                Distributor Information
            </th>
        </tr>
        <tr>
            <td style="border:1px solid #000; width:30%;">Distributor:</td>
            <td style="border:1px solid #000;">{{ ($order->warehouse->warehouse_code) .' - '. ($order->warehouse->warehouse_name) }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">City:</td>
            <td style="border:1px solid #000;">{{ $order->warehouse->city }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Phone:</td>
            <td style="border:1px solid #000;">{{ $order->warehouse->owner_number }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">TIN No:</td>
            <td style="border:1px solid #000;">{{ $order->warehouse->tin_no }}</td>
        </tr>
    </table>

    
     <!-- SALESMAN -->
    <table width="100%" cellpadding="2" cellspacing="0" style="border-collapse:collapse;">
        <tr>
            <th colspan="4" style="text-align:center;">
                Salesman Information
            </th>
        </tr> 
        <tr>
            <td style="border:1px solid #000;">Code:</td>
            <td style="border:1px solid #000;">{{ $order->salesman->osa_code ?? '' }}</td>
            <td style="border:1px solid #000;">Role:</td>
            <td style="border:1px solid #000;">
                {{ $order->salesman->salesmanType->salesman_type_name ?? ''}}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Name:</td>
            <td style="border:1px solid #000;">{{ $order->salesman->name ?? '' }}</td>
            <td style="border:1px solid #000;">Contact No:</td>
            <td style="border:1px solid #000;">{{ $order->salesman->contact_no ?? '' }}</td>
        </tr>
    </table>
    <br>
    <!-- GOODS -->
    <table width="100%" cellpadding="2" cellspacing="0" style="border-collapse:collapse;">
         
        <tr>
            <th style="border:1px solid #000;">S/N</th>
            <th style="border:1px solid #000;">Item</th>
            <th style="border:1px solid #000;">UOM</th>
            <th style="border:1px solid #000;">Unload Qty</th>
        </tr>

        @foreach($orderDetails as $i => $item)
        <tr>
            <td style="border:1px solid #000; text-align:center;">{{ $i+1 }}</td>
            <td style="border:1px solid #000;">{{ $item->item->erp_code }} - {{ $item->item->name }}</td>
            <td style="border:1px solid #000; text-align:center;">{{ $item->uoms->name }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->qty, 0) }}</td>
        </tr>
        @endforeach
    </table>
 
    <table width="100%" cellpadding="2" cellspacing="0">
        <tr>
            <td colspan="2" style="text-align:center; font-size:12px;">
                <span style="font-weight:bolder;">
                    This is a system generated unload
                    <br><br>
                </span>
                Thank you for your business
            </td>
        </tr>
    </table>

</body>

</html>
