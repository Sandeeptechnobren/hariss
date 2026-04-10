<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>RETURN #{{ $return->uuid }}</title>
</head>

<body style="font-family: Arial, Helvetica, sans-serif; font-size:14px;">

    <!-- Header -->
    <table width="100%" cellpadding="8" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <td style="border:1px solid #000; text-align:center; color:red; font-size:22px; font-weight:bold;">
                RETURN
                <div style="font-size:14px; margin-top:4px; color:black;">
                    {{ $return->osa_code }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Seller Details -->
    <table width="100%" cellpadding="2" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <th colspan="2" style="border:1px solid #000; text-align:center;">
                Seller's Detail
            </th>
        </tr>
        <tr>
            <td style="border:1px solid #000; width:30%;">TIN No:</td>
            <td style="border:1px solid #000;">{{ $return->warehouse->tin_no ?? '' }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Agent Name:</td>
            <td style="border:1px solid #000;">
                {{ $return->warehouse->warehouse_name ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Contact No:</td>
            <td style="border:1px solid #000;">
                {{ $return->warehouse->warehouse_manager_contact ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Address:</td>
            <td style="border:1px solid #000;">
                {{ $return->warehouse->city ?? '' }}
            </td>
        </tr>
    </table>

    <!-- Customer Details -->
    <table width="100%" cellpadding="2" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <th colspan="2" style="border:1px solid #000; text-align:center;">
                Customer Information
            </th>
        </tr>
        <tr>
            <td style="border:1px solid #000; width:30%;">Customer:</td>
            <td style="border:1px solid #000;">
                {{ $return->customer->osa_code ?? '' }} - {{ $return->customer->name ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Address:</td>
            <td style="border:1px solid #000;">
                {{ $return->customer->district ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Contact No:</td>
            <td style="border:1px solid #000;">
                {{ $return->customer->contact_no ?? '' }}
            </td>
        </tr>
    </table>

    <!-- Goods Table -->
    <table width="100%" cellpadding="2" cellspacing="0"
        style="border-collapse:collapse;">
        <tr>
            <th style="text-align:center;" colspan="9">Return Details</th>
        </tr>
        <tr>
            <th style="border:1px solid #000;">S/N</th>
            <th style="border:1px solid #000;">Item Code</th>
            <th style="border:1px solid #000;">Description</th>
            <th style="border:1px solid #000;">UOM</th>
            <th style="border:1px solid #000;">Qty</th>
            <th style="border:1px solid #000;">Price</th>
            <th style="border:1px solid #000;">Return Type</th>
            <th style="border:1px solid #000;">Return Reason</th>
            <th style="border:1px solid #000;">Total</th>
        </tr>

        @foreach($returnDetails as $i => $item)
        <tr>
            <td style="border:1px solid #000; text-align:center;">{{ $i + 1 }}</td>
            <td style="border:1px solid #000;">{{ $item->item->code ?? '' }}</td>
            <td style="border:1px solid #000;">{{ $item->item->name ?? '' }}</td>
            <td style="border:1px solid #000; text-align:center;">{{ $item->uom->name ?? '' }}</td>
            <td style="border:1px solid #000; text-align:center;">
                {{ number_format($item->item_quantity) }}
            </td>
            <td style="border:1px solid #000; text-align:right;">
                {{ number_format($item->item_price, 2) }}
            </td>
            <td style="border:1px solid #000; text-align:center;">
                {{ $item->return_type == 1 ? 'Good' : 'Bad' }}
            </td>
            <td style="border:1px solid #000; text-align:center;">
                @if($item->return_reason == 1)
                    Near By Expiry
                @elseif($item->return_reason == 2)
                    Package Issue
                @elseif($item->return_reason == 3)
                    Damage
                @elseif($item->return_reason == 4)
                    Expiry
                @else
                    -
                @endif
            </td>
            <td style="border:1px solid #000; text-align:right;">
                {{ number_format($item->total, 2) }}
            </td>
        </tr>
        @endforeach
    </table>

      <!-- TOTALS (SAME FOOTER STRUCTURE) -->
    <table width="100%" cellspacing="0" style="border-collapse:collapse; border-bottom:2px solid #000; margin-top:5px;">
       
     <tr>
            <td style="width:70%;"></td>
            <td style="width:30%;">
                <table width="100%" cellpadding="4" cellspacing="0" style="border-collapse:collapse;">
                    <tr>
                        <td>
                        </td>
                        <td style="text-align:right;">
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td></td>
            <td>
                <table width="100%" cellpadding="4" cellspacing="0" style="border-collapse:collapse;">
                    <tr>
                        <td></td> 
                         <td style="text-align:right;"></td> 
                    </tr>
                </table>
            </td>
        </tr>

         <tr>
            <td style="vertical-align:middle; padding:6px;">
                <table width="100%" cellpadding="4" cellspacing="0" style="border-collapse:collapse;">
                    <tr>
                        <td style="width:50%; font-weight:bold;">
                        </td>
                        <td style="width:50%; font-weight:bold;">
                        </td>
                    </tr>
                </table>
            </td>
            <td>
                <table width="100%" cellpadding="4" cellspacing="0" style="border-collapse:collapse;">
                    <tr>
                        <td style="font-weight:bold;">Total (UGX)</td>
                        <td style="text-align:right; font-weight:bold;">
                            {{ number_format($return->total, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
 
    </table>

    <!-- Footer -->
    <table width="100%" cellpadding="2" cellspacing="0" style="margin-top:10px;">
        <tr>
            <td style="text-align:center; font-size:12px;">
                <span style="font-weight:bold;">
                    This is a system generated return document and doesn't require signature
                </span>
            </td>
        </tr>
    </table>

</body>

</html>