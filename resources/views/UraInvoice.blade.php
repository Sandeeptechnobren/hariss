<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            border: 1px solid black;
            padding: 5px;
        }

        .no-border td {
            border: none;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        h1 {
            color: red;
            margin: 5px 0;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <table>
        <tr>
            <td class="center">
                <h1>INVOICE/TAX INVOICE</h1>
            </td>
        </tr>
    </table>

    <!-- SELLER DETAILS -->
    <table>
        <tr>
            <td colspan="2" class="center bold">Seller's Detail</td>
        </tr>
        <tr>
            <td width="30%">TIN No:</td>
            <td>{{ $seller->tin ?? '' }}</td>
        </tr>
        <tr>
            <td>Agent Name:</td>
            <td>{{ $seller->name ?? '' }}</td>
        </tr>
        <tr>
            <td>Tel No:</td>
            <td>{{ $seller->phone ?? '' }}</td>
        </tr>
        <tr>
            <td>Address:</td>
            <td>{{ $seller->address ?? '' }}</td>
        </tr>
    </table>

    <!-- CUSTOMER + URA -->
    <table>
        <tr>
            <td colspan="2" class="center bold">Customer's & URA Information</td>
        </tr>
        <tr>
            <td width="30%">Issued Date:</td>
            <td>{{ \Carbon\Carbon::parse($header->invoice_date)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>Customer:</td>
            <td>{{ $customer->name ?? '' }}</td>
        </tr>
        <tr>
            <td>Address:</td>
            <td>{{ $customer->address ?? '' }}</td>
        </tr>
        <tr>
            <td>TIN No:</td>
            <td>{{ $customer->tin ?? '' }}</td>
        </tr>
        <tr>
            <td>Telephone No:</td>
            <td>{{ $customer->phone ?? '' }}</td>
        </tr>
        <tr>
            <td>Invoice No:</td>
            <td>{{ $header->invoice_number }}</td>
        </tr>
        <tr>
            <td>Invoice FDN:</td>
            <td>{{ $header->fdn ?? '' }}</td>
        </tr>
        <tr>
            <td>Verification Code:</td>
            <td>{{ $header->verification_code ?? '' }}</td>
        </tr>
    </table>

    <!-- SALESMAN -->
    <table>
        <tr>
            <td colspan="4" class="center bold">Salesman Information</td>
        </tr>
        <tr>
            <td>Code:</td>
            <td>{{ $salesman->code ?? '' }}</td>
            <td>Role</td>
            <td>Salesman</td>
        </tr>
        <tr>
            <td>Name</td>
            <td>{{ $salesman->name ?? '' }}</td>
            <td>Contact No</td>
            <td>{{ $salesman->phone ?? '' }}</td>
        </tr>
    </table>

    <!-- GOODS -->
    <table>
        <tr>
            <td colspan="6" class="center bold">Goods & Services Details</td>
        </tr>

        <tr class="bold center">
            <th>S/N</th>
            <th>Description</th>
            <th>Qty</th>
            <th>UOM</th>
            <th>Price</th>
            <th>Total (UGX)</th>
        </tr>

        @foreach($items as $i => $item)
        <tr>
            <td class="center">{{ $i+1 }}</td>
            <td>{{ $item->name ?? '' }}</td>
            <td class="center">{{ $item->quantity }}</td>
            <td class="center">{{ $item->uom ?? '' }}</td>
            <td class="right">{{ number_format($item->itemvalue,2) }}</td>
            <td class="right">{{ number_format($item->item_total,2) }}</td>
        </tr>
        @endforeach

        <!-- EMPTY ROWS FOR SPACING -->
        @for($i = count($items); $i < 5; $i++)
            <tr>
            <td>&nbsp;</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            </tr>
            @endfor
    </table>

    <!-- TOTALS -->
    <table>
        <tr>
            <td width="70%" class="no-border"></td>
            <td class="bold">Sub Total (UGX)</td>
            <td class="right">{{ number_format($header->gross_total,2) }}</td>
        </tr>
        <tr>
            <td class="no-border"></td>
            <td class="bold">Discount</td>
            <td class="right">{{ number_format($header->promotion_total,2) }}</td>
        </tr>
        <tr>
            <td><strong>VAT:</strong> {{ number_format($header->vat,2) }}</td>
            <td class="bold">Total (UGX)</td>
            <td class="right">{{ number_format($header->total_amount,2) }}</td>
        </tr>
    </table>

    <!-- FOOTER -->
    <table class="no-border">
        <tr>
            <td class="center bold">
                Invoice Value is Inclusive of 18% VAT
            </td>
        </tr>
        <tr>
            <td class="center">
                This is a system generated invoice and doesn't require any signature
            </td>
        </tr>
        <tr>
            <td class="center">
                Thank you for purchasing Riham products
            </td>
        </tr>
    </table>

</body>

</html>