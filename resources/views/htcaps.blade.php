<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>CAPS DEPOSIT #{{ $order->osa_code }}</title>
</head>

<body style="font-family: Arial, Helvetica, sans-serif; font-size:14px;">

    <!-- HEADER -->
    <table width="100%" cellpadding="8" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <td style="border:1px solid #000; text-align:center; color:red; font-size:22px; font-weight:bold;">
                CAPS DEPOSIT
                <div style="font-size:14px; margin-top:4px; color:black;">
                    {{ $order->osa_code }}
                </div>
            </td>
        </tr>
    </table>

    <!-- SELLER DETAILS -->
    <table width="100%" cellpadding="3" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <th colspan="2" style="border:1px solid #000; text-align:center;">
                Seller's Detail
            </th>
        </tr>
        <tr>
            <td style="border:1px solid #000; width:30%;">Tin No:</td>
            <td style="border:1px solid #000;">
                {{ $order->warehouse->tin_no ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000; width:30%;">Distributor:</td>
            <td style="border:1px solid #000;">
                {{ $order->warehouse->warehouse_code ?? '' }} - {{ $order->warehouse->warehouse_name ?? '' }}
            </td>
        </tr>

        <tr>
            <td style="border:1px solid #000;">Contact No:</td>
            <td style="border:1px solid #000;">
                {{ $order->warehouse->owner_number ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Address:</td>
            <td style="border:1px solid #000;">
                {{ $order->warehouse->city ?? '' }}
            </td>
        </tr>
    </table>

    <!-- DRIVER DETAILS -->
    <table width="100%" cellpadding="3" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <th colspan="2" style="border:1px solid #000; text-align:center;">
                Driver Information
            </th>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Claim Date:</td>
            <td style="border:1px solid #000;">
                {{ \Carbon\Carbon::parse($order->claim_date)->format('d M Y') }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000; width:30%;">Driver Code:</td>
            <td style="border:1px solid #000;">
                {{ $order->driverinfo->osa_code ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Driver Name:</td>
            <td style="border:1px solid #000;">
                {{ $order->driverinfo->driver_name ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Contact No:</td>
            <td style="border:1px solid #000;">
                {{ $order->driverinfo->contactno ?? '' }}
            </td>
        </tr>

    </table>

    <!-- ITEMS -->
    <table width="100%" cellpadding="3" cellspacing="0"
        style="border-collapse:collapse;">
        <tr>
            <th colspan="8" style="text-align:center;">
                Caps Details
            </th>
        </tr>
        <tr>
            <th style="border:1px solid #000;">S/N</th>
            <th style="border:1px solid #000;">Item Code</th>
            <th style="border:1px solid #000;">Item Name</th>
            <th style="border:1px solid #000;">UOM</th>
            <th style="border:1px solid #000;">Qty</th>
            <th style="border:1px solid #000;">Collected Qty</th>
            <th style="border:1px solid #000;">Receive Amount</th>
            <th style="border:1px solid #000;">Remarks</th>
        </tr>

        @foreach($orderDetails as $i => $item)
        <tr>
            <td style="border:1px solid #000; text-align:center;">{{ $i+1 }}</td>
            <td style="border:1px solid #000;">{{ $item->item->code ?? '' }}</td>
            <td style="border:1px solid #000;">{{ $item->item->name ?? '' }}</td>
            <td style="border:1px solid #000; text-align:center;">{{ $item->uoms->name ?? '' }}</td>
            <td style="border:1px solid #000; text-align:center;">{{ number_format($item->quantity, 0) }}</td>
            <td style="border:1px solid #000; text-align:center;">{{ number_format($item->receive_qty, 0) }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->receive_amount, 0) }}</td>
            <td style="border:1px solid #000;">{{ $item->remarks ?? '' }}</td>
        </tr>
        @endforeach
    </table>

    <!-- FOOTER -->
    <table width="100%" cellpadding="3" cellspacing="0" style="margin-top:5px;">
        <tr>
            <td style="text-align:center; font-size:12px;">
                <span style="font-weight:bold;">
                    This is a system generated document and doesn't require any signature
                </span><br><br>
                Thank you
            </td>
        </tr>
    </table>

</body>

</html>