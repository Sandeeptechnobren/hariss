<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>INVOICE #{{ $header->invoice_code }}</title>
</head>

<body style="font-family: Arial, Helvetica, sans-serif; font-size:14px;">
    <table width="100%" cellpadding="8" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <td style="border:1px solid #000; text-align:center; color:red; font-size:22px; font-weight:bold;">
                INVOICE / TAX INVOICE
                <div style="font-size:14px; margin-top:4px; color:black;">
                    {{ $header->invoice_code }}
                </div>
            </td>
        </tr>
    </table>
    <table width="100%" cellpadding="2" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <th colspan="2" style="border:1px solid #000; text-align:center;">
                Seller's Detail
            </th>
        </tr>
        <tr>
            <td style="border:1px solid #000; width:30%;">TIN No:</td>
            <td style="border:1px solid #000;">{{ $header->warehouse->tin_no ?? ''}}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Agent Name:</td>
            <td style="border:1px solid #000;">{{$header->warehouse->warehouse_code ?? ''}} - {{ $header->warehouse->warehouse_name ?? ''}}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Contact No:</td>
            <td style="border:1px solid #000;">{{ $header->warehouse->owner_number ?? ''}}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Address:</td>
            <td style="border:1px solid #000;">{{ $header->warehouse->city ?? ''}}</td>
        </tr>
    </table>
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
                {{ \Carbon\Carbon::parse($header->invoice_date)->format('d M Y') }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Customer:</td>
            <td style="border:1px solid #000;">{{$header->customer->osa_code ?? ''}} - {{ $header->customer->name ?? '' }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Address:</td>
            <td style="border:1px solid #000;">{{ $header->customer->street ?? '' }}{{ $header->customer->town ?? '' }}{{ $header->customer->landmark ?? '' }}{{ $header->customer->district ?? '' }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">TIN No:</td>
            <td style="border:1px solid #000;">{{ $header->customer->tin_no ?? '' }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Contact No:</td>
            <td style="border:1px solid #000;">{{ $header->customer->contact_no ?? '' }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Invoice No:</td>
            <td style="border:1px solid #000;">{{ $header->ura_invoice_id }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Invoice FDN:</td>
            <td style="border:1px solid #000;">{{ $header->ura_invoice_no }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Verification Code:</td>
            <td style="border:1px solid #000;">{{ $header->ura_antifake_code }}</td>
        </tr>
    </table>
    <table width="100%" cellpadding="2" cellspacing="0" style="border-collapse:collapse;">
        <tr>
            <th colspan="4" style="text-align:center;">
                Salesman Information
            </th>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Code:</td>
            <td style="border:1px solid #000;">{{ $header->salesman->osa_code ?? '' }}</td>
            <td style="border:1px solid #000;">Role:</td>
            <td style="border:1px solid #000;">{{$header->salesman->salesmanType->salesman_type_name ?? ''}}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Name:</td>
            <td style="border:1px solid #000;">{{ $header->salesman->name ?? '' }}</td>
            <td style="border:1px solid #000;">Contact No:</td>
            <td style="border:1px solid #000;">{{ $header->salesman->contact_no ?? '' }}</td>
        </tr>
    </table>
    <table width="100%" cellpadding="2" cellspacing="0"
        style="border-collapse:collapse;">
        <tr>
            <th style="text-align:center;" colspan="6">Goods & Services Details</th>

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
        @foreach($details as $i => $item)
        <tr>
            <td style="border:1px solid #000; text-align:center;">{{ $i+1 }}</td>
            <td style="border:1px solid #000;">{{ ($item->item?->erp_code ?? '') . ' - ' . ($item->item?->name ?? '') }}</td>
            <td style="border:1px solid #000; text-align:center;">{{ $item->uoms->name ?? '' }}</td>
            <td style="border:1px solid #000; text-align:center;">{{ $item->quantity }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->itemvalue, 2) }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->vat, 2) }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->net_total, 2) }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->item_total, 2) }}</td>
        </tr>
        @endforeach
    </table>
    <table width="100%" cellspacing="0" style="border-collapse:collapse; border-bottom:2px solid #000; margin-top:5px;">
        <tr>
            <td style="width:70%;"></td>
            <td style="width:30%;">
                <table width="100%" cellpadding="4" cellspacing="0"
                    style="border-collapse:collapse;">
                    <tr>
                        <td>
                            <!-- Sub Total({{$header->currency_name}}) -->
                        </td>
                        <td style="text-align:right;">
                            <!-- {{ number_format($header->sub_total, 2) }} -->
                        </td>
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
                        <!-- <td>Discount</td> -->
                        <!-- <td style="text-align:right;">0.00</td> -->
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="vertical-align:middle; padding:6px;">
                <table width="100%" cellpadding="4" cellspacing="0"
                    style="border-collapse:collapse;">
                    <tr>
                        <td style="width:50%; font-weight:bold;">
                            VAT: {{ number_format($header->vat, 2) }}
                        </td>
                        <td style="width:50%; font-weight:bold;">
                            NET: {{ number_format($header->net_total, 2) }}
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
                            {{ number_format($header->total_amount, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

    </table>
    <span style="font-weight:bolder ;">Invoice Value is Inclusive of 18% VAT </span>
    <table width="100%" cellpadding="2" cellspacing="0">
        <tr>
            <td colspan="2" style="text-align:center; font-size:12px;">
                <span style="font-weight:bolder ;"> This is a system generated invoice and doesn't require any signature <br><br></span>
                Thank you for purchasing Riham products
            </td>
        </tr>
    </table>
</body>

</html>