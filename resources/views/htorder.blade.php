<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Company Order #{{ $order->order_code }}</title>
</head>

<body style="font-family: Arial, Helvetica, sans-serif; font-size:14px;">

    <table width="100%" cellpadding="8" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <td style="border:1px solid #000; text-align:center; color:red; font-size:22px; font-weight:bold;">
                Company Order
                <div style="font-size:14px; margin-top:4px; color:black;">
                    {{ $order->order_code }}
                </div>
            </td>
        </tr>
    </table>

    <!-- SELLER -->
    <!-- <table width="100%" cellpadding="2" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <th colspan="2" style="border:1px solid #000; text-align:center;">
                Seller's Detail
            </th>
        </tr>
        <tr>
            <td style="border:1px solid #000; width:30%;">TIN No:</td>
            <td style="border:1px solid #000;">{{ $order->warehouse->tin_no ?? ''}}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Agent Name:</td>
            <td style="border:1px solid #000;">
                {{ $order->warehouse->warehouse_code ?? '' }} -
                {{ $order->warehouse->warehouse_name ?? ''}}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Tel No:</td>
            <td style="border:1px solid #000;">{{ $order->warehouse->owner_number ?? ''}}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Address:</td>
            <td style="border:1px solid #000;">{{ $order->warehouse->city ?? ''}}</td>
        </tr>
    </table> -->

    <table width="100%" cellpadding="2" cellspacing="0"
    style="border-collapse:collapse; border:1px solid #000;">

    <tr>
        <th colspan="2" style="border:1px solid #000; text-align:center;">
            Seller's Detail
        </th>
    </tr>

    @if(!empty($order->warehouse_id) && $order->warehouse)

        {{-- Warehouse Seller --}}
        <tr>
            <td style="border:1px solid #000; width:30%;">TIN No:</td>
            <td style="border:1px solid #000;">{{ $order->warehouse->tin_no ?? '' }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Agent Name:</td>
            <td style="border:1px solid #000;">
                {{ $order->warehouse->warehouse_code ?? '' }} -
                {{ $order->warehouse->warehouse_name ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Tel No:</td>
            <td style="border:1px solid #000;">{{ $order->warehouse->owner_number ?? '' }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Address:</td>
            <td style="border:1px solid #000;">{{ $order->warehouse->city ?? '' }}</td>
        </tr>

    @elseif(!empty($order->company_id) && $order->company)

        {{-- Company Seller --}}
        <tr>
            <td style="border:1px solid #000; width:30%;">TIN No:</td>
            <td style="border:1px solid #000;">{{ $order->company->tin_number ?? '' }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Distributor:</td>
            <td style="border:1px solid #000;">
                {{ $order->company->company_code ?? '' }} -
                {{ $order->company->company_name ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Tel No:</td>
            <td style="border:1px solid #000;">{{ $order->company->primary_contact ?? '' }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Address:</td>
            <td style="border:1px solid #000;">{{ $order->company->city ?? '' }}</td>
        </tr>

    @else

        {{-- No Data --}}
        <tr>
            <td colspan="2" style="border:1px solid #000; text-align:center;">
                No seller information
            </td>
        </tr>

    @endif

</table>

    <!-- CUSTOMER -->
    <!-- <table width="100%" cellpadding="2" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <th colspan="2" style="border:1px solid #000; text-align:center;">
                Buyer
            </th>
        </tr>
        <tr>
            <td style="border:1px solid #000; width:30%;">Order Date:</td>
            <td style="border:1px solid #000;"> 
                {{ \Carbon\Carbon::parse($order->created_at)->format('d M Y') }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Customer:</td>
            <td style="border:1px solid #000;">
                {{ $order->customer->osa_code ?? '' }} -
                {{ $order->customer->name ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Address:</td>
            <td style="border:1px solid #000;">
                {{ $order->customer->street ?? '' }}
                {{ $order->customer->town ?? '' }}
                {{ $order->customer->landmark ?? '' }}
                {{ $order->customer->district ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Telephone:</td>
            <td style="border:1px solid #000;">{{ $order->customer->contact_no ?? '' }}</td>
        </tr>
    </table> -->
 
    <table width="100%" cellpadding="2" cellspacing="0"
    style="border-collapse:collapse; border:1px solid #000;">

    <tr>
        <th colspan="2" style="border:1px solid #000; text-align:center;">
            Buyer
        </th>
    </tr>

    <tr>
        <td style="border:1px solid #000; width:30%;">Order Date:</td>
        <td style="border:1px solid #000;">
            {{ \Carbon\Carbon::parse($order->created_at)->format('d M Y') }}
        </td>
    </tr>

    <tr>
        <td style="border:1px solid #000;">Customer:</td>
        <td style="border:1px solid #000;">
            {{ $order->customer->osa_code ? $order->customer->osa_code . ' - ' : '' }}
            {{ $order->customer->business_name ?? '' }}
        </td>
    </tr>

    <tr>
        <td style="border:1px solid #000;">Address:</td>
        <td style="border:1px solid #000;">
            {{ $order->customer->town ?? '' }}
        </td>
    </tr>

    <tr>
        <td style="border:1px solid #000;">Telephone:</td>
        <td style="border:1px solid #000;">
            {{ $order->customer->contact_number ?? '' }}
        </td>
    </tr>

</table>
    <!-- GOODS -->
    <table width="100%" cellpadding="2" cellspacing="0" style="border-collapse:collapse;">
        <tr>
            <th style="text-align:center;" colspan="6">Goods & Services Details</th>
        </tr>
        <tr>
            <th style="border:1px solid #000;">S/N</th>
            <th style="border:1px solid #000;">Item</th>
            <th style="border:1px solid #000;">UOM</th>
            <th style="border:1px solid #000;">Qty</th>
            <th style="border:1px solid #000;">Price</th>
            <th style="border:1px solid #000;">VAT</th>
            <th style="border:1px solid #000;">Net</th>
            <th style="border:1px solid #000;">Excise</th>
            <th style="border:1px solid #000;">Total</th>
        </tr>

        @foreach($orderDetails as $i => $item)
        <tr>
            <td style="border:1px solid #000; text-align:center;">{{ $i+1 }}</td>
            <td style="border:1px solid #000;">{{ ($item->item?->code ?? '') . ' - ' . ($item->item?->name ?? '') }}</td>
            <td style="border:1px solid #000; text-align:center;">{{ $item->uoms->name ?? '' }}</td>
            <td style="border:1px solid #000; text-align:center;">{{ (int) $item->quantity }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->item_price, 2) }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->vat, 2) }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->net, 2) }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->excise, 2) }}</td>
            <td style="border:1px solid #000; text-align:right;"> {{ number_format($item->total, 2) }}</td>
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
                            NET: {{ number_format($order->net_amount, 2) }}
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

    <span style="font-weight:bolder;">Order Value is Inclusive of VAT</span>

    <table width="100%" cellpadding="2" cellspacing="0">
        <tr>
            <td colspan="2" style="text-align:center; font-size:12px;">
                <span style="font-weight:bolder;">
                    This is a system generated order and doesn't require any signature
                    <br><br>
                </span>
                Thank you for your business
            </td>
        </tr>
    </table>

</body>

</html>