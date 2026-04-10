
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Sales Load #{{ $order->osa_code }}</title>
</head>

<body style="font-family: Arial, Helvetica, sans-serif; font-size:14px;">

    <table width="100%" cellpadding="8" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <td style="border:1px solid #000; text-align:center; color:red; font-size:22px; font-weight:bold;">
               Sales Load
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
            <td style="border:1px solid #000; width:30%;">Load Date:</td>
            <td style="border:1px solid #000;">{{ $order->created_at }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Accept Date:</td>
            <td style="border:1px solid #000;">{{ $order->accept_time }}</td>
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

    <!-- CUSTOMER -->
    <!-- <table width="100%" cellpadding="2" cellspacing="0" style="border-collapse:collapse;">
        <tr>
            <th colspan="2" style="border:1px solid #000; text-align:center;">
                Salesman Information
            </th>
        </tr>
        
        <tr>
            <td style="border:1px solid #000; width:30%;">Salesman:</td>
            <td style="border:1px solid #000;"> 
                {{ ($order->salesman->osa_code ?? '') . ' - ' . ($order->salesman->name ?? '')}}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Phone:</td>
            <td style="border:1px solid #000;">
               {{ $order->salesman->contact_no ?? '' }}
            </td>
        </tr>
    </table> -->
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
            <th style="border:1px solid #000;">Load Qty</th>
        </tr>

        @foreach($orderDetails as $i => $item)
        <tr>
            <td style="border:1px solid #000; text-align:center;">{{ $i+1 }}</td>
            <td style="border:1px solid #000;">{{ $item->item->erp_code }} - {{ $item->item->name }}</td>
            <td style="border:1px solid #000; text-align:center;">{{ $item->Uom->name }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->qty, 0) }}</td>
        </tr>
        @endforeach
    </table>
 
    <table width="100%" cellpadding="2" cellspacing="0">
        <tr>
            <td colspan="2" style="text-align:center; font-size:12px;">
                <span style="font-weight:bolder;">
                    This is a system generated load
                    <br><br>
                </span>
                Thank you for your business
            </td>
        </tr>
    </table>

</body>

</html>
