<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>CapsCollection #{{ $delivery->code }}</title>
</head>

<body style="font-family: Arial, Helvetica, sans-serif; font-size:14px;">

    <table width="100%" cellpadding="8" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <td style="border:1px solid #000; text-align:center; color:red; font-size:22px; font-weight:bold;">
                CAPS COLLECTION NOTE
                <div style="font-size:14px; margin-top:4px; color:black;">
                    {{ $delivery->code }}
                </div>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="2" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <th colspan="2" style="border:1px solid #000; text-align:center;">
                Distributor's Detail
            </th>
        </tr>
        <tr>
            <td style="border:1px solid #000; width:30%;">TIN No:</td>
            <td style="border:1px solid #000;">{{ $delivery->warehouse->tin_no ?? ''}}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Agent Name:</td>
            <td style="border:1px solid #000;">
                {{ $delivery->warehouse->warehouse_code ?? '' }} -
                {{ $delivery->warehouse->warehouse_name ?? ''}}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Tel No:</td>
            <td style="border:1px solid #000;">{{ $delivery->warehouse->owner_number ?? ''}}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Address:</td>
            <td style="border:1px solid #000;">{{ $delivery->warehouse->city ?? ''}}</td>
        </tr>
    </table>

    <table width="100%" cellpadding="2" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <th colspan="2" style="border:1px solid #000; text-align:center;">
                Customer Information
            </th>
        </tr>
        <tr>
            <td style="border:1px solid #000; width:30%;">Caps Collection Date:</td>
            <td style="border:1px solid #000;">
                {{ \Carbon\Carbon::parse($delivery->created_at)->format('d M Y') }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Customer:</td>
            <td style="border:1px solid #000;">
                {{ $delivery->customerdata->osa_code ?? '' }} -
                {{ $delivery->customerdata->name ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Address:</td>
            <td style="border:1px solid #000;">
                {{ $delivery->customerdata->street ?? '' }}
                {{ $delivery->customerdata->town ?? '' }}
                {{ $delivery->customerdata->landmark ?? '' }}
                {{ $delivery->customerdata->district ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Telephone:</td>
            <td style="border:1px solid #000;">
                {{ $delivery->customerdata->contact_no ?? '' }}
            </td>
        </tr>
    </table>

    <!-- <table width="100%" cellpadding="2" cellspacing="0" style="border-collapse:collapse;">
        <tr>
            <th colspan="4" style="text-align:center;">
                Salesman Information
            </th>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Code:</td>
            <td style="border:1px solid #000;">
                {{ $delivery->salesman->osa_code ?? '' }}
            </td>
            <td style="border:1px solid #000;">Role:</td>
            <td style="border:1px solid #000;">
                {{ $delivery->salesman->salesmanType->salesman_type_name ?? ''}}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Name:</td>
            <td style="border:1px solid #000;">
                {{ $delivery->salesman->name ?? '' }}
            </td>
            <td style="border:1px solid #000;">Contact No:</td>
            <td style="border:1px solid #000;">
                {{ $delivery->salesman->contact_no ?? '' }}
            </td>
        </tr>
    </table> -->

    <table width="100%" cellpadding="2" cellspacing="0" style="border-collapse:collapse;">
        <tr>
            <th style="text-align:center;" colspan="6">
                Goods & Services Details
            </th>
        </tr>
        <tr>
            <th style="border:1px solid #000;">S/N</th>
            <th style="border:1px solid #000;">Item</th>
            <th style="border:1px solid #000;">Price</th>
            <th style="border:1px solid #000;">UOM</th>
            <th style="border:1px solid #000;">Collected Qty</th>
            <th style="border:1px solid #000;">Total</th>
        </tr>

        @foreach($deliveryDetails as $i => $item)
        <tr>
            <td style="border:1px solid #000; text-align:center;">{{ $i+1 }}</td>
            <td style="border:1px solid #000;">{{ ($item->item?->erp_code ?? '') . ' - ' . ($item->item?->name ?? '') }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->price, 2) }}</td>
            <td style="border:1px solid #000; text-align:center;"> {{ $item->uom2->name ?? '' }}</td>
            <td style="border:1px solid #000; text-align:center;">{{ $item->collected_quantity }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->total, 2) }}</td>
        </tr>
        @endforeach
    </table>

    <!-- <table width="100%" cellspacing="0"
        style="border-collapse:collapse; border-bottom:2px solid #000; margin-top:5px;">
        <tr>
            <td style="width:70%;"></td>
            <td style="width:30%;">
                <table width="100%" cellpadding="4" cellspacing="0"
                    style="border-collapse:collapse;">
                    <tr>
                        <td></td>
                        <td style="text-align:right;">
                            {{ number_format($delivery->net_amount, 2) }} -->
                        <!-- </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <table width="100%" cellpadding="4" cellspacing="0"
                    style="border-collapse:collapse;">
                    <tr>
                        <td>Discount</td>
                        <td style="text-align:right;">0.00</td> -->
                    <!-- </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="vertical-align:middle; padding:6px;">
                <table width="100%" cellpadding="4" cellspacing="0"
                    style="border-collapse:collapse;">
                    <tr>
                        <td style="width:50%; font-weight:bold;">
                            VAT: {{ number_format($delivery->vat, 2) }}
                        </td>
                        <td style="width:50%; font-weight:bold;">
                            NET: {{ number_format($delivery->net_amount, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
            <td>
                <table width="100%" cellpadding="4" cellspacing="0"
                    style="border-collapse:collapse;">
                    <tr>
                        <td style="font-weight:bold;">Total (UGX)</td>
                        <td style="text-align:right; font-weight:bold;">
                            {{ number_format($delivery->total, 2) }} 
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>  -->

    <span style="font-weight:bolder;">
        Caps Value is Inclusive of Collection
    </span>

    <table width="100%" cellpadding="2" cellspacing="0">
        <tr>
            <td colspan="2" style="text-align:center; font-size:12px;">
                <span style="font-weight:bolder;">
                    This is a system generated caps note and doesn't require any signature
                    <br><br>
                </span>
                Thank you for your business
            </td>
        </tr>
    </table>

</body>

</html>