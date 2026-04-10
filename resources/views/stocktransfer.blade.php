<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>STOCK TRANSFER #{{ $delivery->delivery_code }}</title>
</head>

<body style="font-family: Arial, Helvetica, sans-serif; font-size:14px;">

    <table width="100%" cellpadding="8" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <td style="border:1px solid #000; text-align:center; color:red; font-size:22px; font-weight:bold;">
                STOCK TRANSFER
                <div style="font-size:14px; margin-top:4px; color:black;">
                    {{ $delivery->osa_code }}
                </div>
            </td>
        </tr>
    </table>

    <!-- SELLER -->
    <table width="100%" cellpadding="2" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <th colspan="2" style="border:1px solid #000; text-align:center;">
                Stock Transfer Details
            </th>
        </tr>
        <tr>
            <td style="border:1px solid #000; width:30%;">Parent Distributor:</td>
            <td style="border:1px solid #000;">{{ $delivery->sourceWarehouse->warehouse_code ?? '' }} - {{ $delivery->sourceWarehouse->warehouse_name ?? ''}}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Child Distributor:</td>
            <td style="border:1px solid #000;">
                {{ $delivery->destinyWarehouse->warehouse_code ?? '' }} - {{ $delivery->destinyWarehouse->warehouse_name ?? ''}}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Transfer Date:</td>
            <td style="border:1px solid #000;">{{ \Carbon\Carbon::parse($delivery->transfer_date)->format('d M Y') }}</td>
        </tr>
         
    </table>

    <!-- GOODS -->
    <table width="100%" cellpadding="2" cellspacing="0" style="border-collapse:collapse;">
        <tr>
            <th style="text-align:center;" colspan="4">Transfer Items</th>
        </tr>
        <tr>
            <!-- <th style="border:1px solid #000;">S/N</th> -->
            <th style="border:1px solid #000;">Item</th>
            <th style="border:1px solid #000;">Transfer Qty</th>
            <th style="border:1px solid #000;">Parent Stock</th>
            <th style="border:1px solid #000;">Child Stock</th>
        </tr>

        @foreach($deliveryDetails as $i => $item)
        <tr>
            <!-- <td style="border:1px solid #000; text-align:center;">{{ $i+1 }}</td> -->
            <td style="border:1px solid #000;">{{ $item['erp_code'] }} - {{ $item['item_name'] }}</td>
            <td style="border:1px solid #000; text-align:center;">{{ $item['transfer_qty'] }}</td>
            <td style="border:1px solid #000; text-align:center;">{{ $item['source_warehouse_stock'] }}</td>
            <td style="border:1px solid #000; text-align:right;"> {{ $item['destiny_warehouse_stock'] }}</td>
        </tr>
        @endforeach
    </table>
 
    <span style="font-weight:bolder;">Stock Value is Inclusive of Distributor Stock Qty</span>

    <table width="100%" cellpadding="2" cellspacing="0">
        <tr>
            <td colspan="2" style="text-align:center; font-size:12px;">
                <span style="font-weight:bolder;">
                    This is a system generated stock transfer note and doesn't require any signature
                    <br><br>
                </span>
                Thank you for your business
            </td>
        </tr>
    </table>

</body>

</html>