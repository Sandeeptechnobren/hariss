<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>PURCHASE ORDER #{{ $order->order_code }}</title>
</head>

<body style="font-family: Arial, Helvetica, sans-serif; font-size:14px;">

    <!-- Header -->
    <table width="100%" cellpadding="8" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <td style="border:1px solid #000; text-align:center; color:red; font-size:22px; font-weight:bold;">
                PURCHASE ORDER
                <div style="font-size:14px; margin-top:4px; color:black;">
                    {{ $order->order_code }}
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

        @if(!empty($order->warehouse_id) && $order->warehouse)
            <tr>
                <td style="border:1px solid #000; width:30%;">TIN No:</td>
                <td style="border:1px solid #000;">{{ $order->warehouse->tin_no ?? '' }}</td>
            </tr>
            <tr>
                <td style="border:1px solid #000;">Agent Name:</td>
                <td style="border:1px solid #000;">
                    {{ $order->warehouse->warehouse_code ?? '' }} - {{ $order->warehouse->warehouse_name ?? '' }}
                </td>
            </tr>
            <tr>
                <td style="border:1px solid #000;">Contact No:</td>
                <td style="border:1px solid #000;">{{ $order->warehouse->owner_number ?? '' }}</td>
            </tr>
            <tr>
                <td style="border:1px solid #000;">Address:</td>
                <td style="border:1px solid #000;">{{ $order->warehouse->city ?? '' }}</td>
            </tr>

        @elseif(!empty($order->company_id) && $order->company)

            <tr>
                <td style="border:1px solid #000;">TIN No:</td>
                <td style="border:1px solid #000;">{{ $order->company->tin_number ?? '' }}</td>
            </tr>
            <tr>
                <td style="border:1px solid #000;">Company Name:</td>
                <td style="border:1px solid #000;">
                    {{ $order->company->company_code ?? '' }} - {{ $order->company->company_name ?? '' }}
                </td>
            </tr>
            <tr>
                <td style="border:1px solid #000;">Contact No:</td>
                <td style="border:1px solid #000;">{{ $order->company->primary_contact ?? '' }}</td>
            </tr>
            <tr>
                <td style="border:1px solid #000;">Address:</td>
                <td style="border:1px solid #000;">{{ $order->company->city ?? '' }}</td>
            </tr>

        @endif
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
                {{ $order->customer->osa_code ?? '' }} - {{ $order->customer->business_name ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Address:</td>
            <td style="border:1px solid #000;">
                {{ $order->customer->town ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Contact No:</td>
            <td style="border:1px solid #000;">
                {{ $order->customer->contact_number ?? '' }}
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table width="100%" cellpadding="2" cellspacing="0"
        style="border-collapse:collapse;">
        <tr>
            <th style="text-align:center;" colspan="8">Purchase Details</th>
        </tr>
        <tr>
            <th style="border:1px solid #000;">Item</th>
            <th style="border:1px solid #000;">UOM</th>
            <th style="border:1px solid #000;">Qty</th>
            <th style="border:1px solid #000;">Price</th>
            <th style="border:1px solid #000;">Excise</th>
            <th style="border:1px solid #000;">Net</th>
            <th style="border:1px solid #000;">VAT</th>
            <th style="border:1px solid #000;">Total</th>
        </tr>

        @foreach($orderDetails as $i => $item)
        <tr>
            <td style="border:1px solid #000;">{{ ($item->item->code ?? '') . ' - ' . ($item->item->name ?? '') }}</td>
            <td style="border:1px solid #000; text-align:center;">{{ $item->uom->name ?? '' }}</td>
            <td style="border:1px solid #000; text-align:center;">
                {{ number_format($item->quantity, 0) }}
            </td>
            <td style="border:1px solid #000; text-align:right;">
                {{ number_format($item->item_price, 2) }}
            </td>
            <td style="border:1px solid #000; text-align:right;">
                {{ number_format($item->excise, 2) }}
            </td>
            <td style="border:1px solid #000; text-align:right;">
                {{ number_format($item->net, 2) }}
            </td>
            <td style="border:1px solid #000; text-align:right;">
                {{ number_format($item->vat, 2) }}
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
                            <!-- Sub Total ({{ $order->currency }}) -->
                        </td>
                        <td style="text-align:right;">
                            <!-- {{ number_format($order->net_amount, 2) }} -->
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
                        <!-- <td>Discount</td> -->
                        <!-- <td style="text-align:right;">0.00</td> -->
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td style="vertical-align:middle; padding:6px;">
                <table width="100%" cellpadding="4" cellspacing="0" style="border-collapse:collapse;">
                    <tr>
                        <td style="width:50%; font-weight:bold;">
                            VAT: {{ number_format($order->vat, 2) }}
                        </td>
                        <td style="width:50%; font-weight:bold;">
                            NET: {{ number_format($order->net, 2) }}
                        </td>
                        <td style="width:50%; font-weight:bold;">
                            Excise: {{ number_format($order->excise, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
            <td>
                <table width="100%" cellpadding="4" cellspacing="0" style="border-collapse:collapse;">
                    <tr>
                        <td style="font-weight:bold;">Total ({{ $order->currency }}) : {{ number_format($order->total, 2) }}</td>
                        <!-- <td style="text-align:right; font-weight:bold;">
                            {{ number_format($order->total, 2) }}
                        </td> -->
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <table width="100%" cellpadding="2" cellspacing="0">
        <tr>
            <td style="text-align:center; font-size:12px;">
                <span style="font-weight:bold;">
                    This is a system generated purchase order and doesn't require signature
                </span>
            </td>
        </tr>
    </table>

</body>

</html>