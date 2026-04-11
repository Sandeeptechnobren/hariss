<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>COMPANY_INVOICE #{{ $order->invoice_code }}</title>
</head>

<body style="font-family: Arial, Helvetica, sans-serif; font-size:14px;">

    <!-- HEADER -->
    <table width="100%" cellpadding="8" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <td style="border:1px solid #000; text-align:center; color:red; font-size:22px; font-weight:bold;">
                COMPANY TAX INVOICE
                <div style="font-size:14px; margin-top:4px; color:black;">
                    {{ $order->invoice_code }}
                </div>
            </td>
        </tr>
    </table>

    <!-- SELLER -->
    <table width="100%" cellpadding="2" cellspacing="0"
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
                {{ $order->warehouse->warehouse_code ?? ''}} - {{ $order->warehouse->warehouse_name ?? ''}}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Contact No:</td>
            <td style="border:1px solid #000;">{{ $order->warehouse->owner_number ?? ''}}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Address:</td>
            <td style="border:1px solid #000;">{{ $order->warehouse->city ?? ''}}</td>
        </tr>
    </table>

    <!-- CUSTOMER + URA -->
    <table width="100%" cellpadding="2" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <th colspan="2" style="border:1px solid #000; text-align:center;">
                Customer's & URA Information
            </th>
        </tr>
        <tr>
            <td style="border:1px solid #000; width:30%;">Issued Date:</td>
            <td style="border:1px solid #000;">
                {{ $order->invoice_date ? \Carbon\Carbon::parse($order->invoice_date)->format('d M Y') : '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Customer:</td>
            <td style="border:1px solid #000;">
                {{ optional($order->customer)->osa_code ?? '' }} - {{ $order->customer->business_name ?? '' }}
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
                {{ $order->customer->contact_no ?? '' }}
            </td>
        </tr>
    </table>

    <!-- SALESMAN -->
    <table width="100%" cellpadding="2" cellspacing="0"
        style="border-collapse:collapse;">
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
                {{ $order->salesman->salesmanType->salesman_type_name ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Name:</td>
            <td style="border:1px solid #000;">{{ $order->salesman->name ?? '' }}</td>
            <td style="border:1px solid #000;">Contact No:</td>
            <td style="border:1px solid #000;">{{ $order->salesman->contact_no ?? '' }}</td>
        </tr>
    </table>

    <!-- ITEMS -->
    <table width="100%" cellpadding="2" cellspacing="0"
        style="border-collapse:collapse;">
        <tr>
            <th colspan="8" style="text-align:center;">
                Goods & Services Details
            </th>
        </tr>

        <tr>
            <th style="border:1px solid #000;">S/N</th>
            <th style="border:1px solid #000;">Item</th>
            <th style="border:1px solid #000;">UOM</th>
            <th style="border:1px solid #000;">Qty</th>
            <th style="border:1px solid #000;">Price</th>
            <th style="border:1px solid #000;">Vat</th>
            <th style="border:1px solid #000;">Net</th>
            <th style="border:1px solid #000;">Total</th>
        </tr>

        @foreach($orderDetails as $i => $item)
        <tr>
            <td style="border:1px solid #000; text-align:center;">{{ $i+1 }}</td>
            <td style="border:1px solid #000;">
                {{ $item->item->code ?? '' }} - {{ $item->item->name ?? '' }}
            </td>
            <td style="border:1px solid #000; text-align:center;">{{ $item->uoms->name ?? '' }}</td>
            <td style="border:1px solid #000; text-align:center;">{{ $item->quantity }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->item_price, 2) }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->vat, 2) }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->net, 2) }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->total, 2) }}</td>
        </tr>
        @endforeach
    </table>

    <!-- TOTAL INLINE (IMPORTANT FIX) -->
    <table width="100%" style="margin-top:15px;">
        <tr>
            <td style="width:33%; font-weight:bold;">VAT: {{ number_format($order->vat, 2) }}</td>
            <td style="width:33%; font-weight:bold;">NET: {{ number_format($order->net, 2) }}</td>
            <td style="width:33%; text-align:right; font-weight:bold;">
                Total (UGX) {{ number_format($order->total, 2) }}
            </td>
        </tr>
    </table>

    <hr>

    <!-- FOOTER -->
    <div style="font-weight:bold;">
        Invoice Value is Inclusive of VAT
    </div>

    <div style="text-align:center; font-size:12px;">
        <strong>This is a system generated invoice and doesn't require any signature</strong><br><br>
        Thank you for purchasing Riham products
    </div>

</body>

</html>