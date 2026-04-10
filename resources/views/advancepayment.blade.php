<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Advance Payment #{{ $delivery->osa_code }}</title>
</head>

<body style="font-family: Arial, Helvetica, sans-serif; font-size:14px;">

    <table width="100%" cellpadding="8" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <td style="border:1px solid #000; text-align:center; color:red; font-size:22px; font-weight:bold;">
                Advance Payment
                <div style="font-size:14px; margin-top:4px; color:black;">
                    {{ $delivery->osa_code }}
                </div>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="2" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <th colspan="2" style="border:1px solid #000; text-align:center;">
                Payment Information
            </th>
        </tr>
        <tr>
            <td style="border:1px solid #000; width:30%;">Amount:</td>
            <td style="border:1px solid #000;">{{ number_format($delivery->amount,2) ?? ''}}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Payment Type:</td>
            <td style="border:1px solid #000;">
               {{ 
                    $delivery->payment_type == 1 ? 'Cash' : 
                    ($delivery->payment_type == 2 ? 'Cheque' : 
                    ($delivery->payment_type == 3 ? 'Transfer' : '')) 
                }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Bank:</td>
            <td style="border:1px solid #000;">{{ $delivery->companyBank->bank_name ?? ''}}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Agent:</td>
            <td style="border:1px solid #000;">{{ $delivery->agent->business_name ?? ''}}</td>
        </tr>
    </table>
     @if($delivery->payment_type== 2)
     <table width="100%" cellpadding="2" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <th colspan="2" style="border:1px solid #000; text-align:center;">
                Cheque information
            </th>
        </tr>
        <tr>
            <td style="border:1px solid #000; width:30%;">Cheque number:</td>
            <td style="border:1px solid #000;">
                {{ $delivery->cheque_no ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Cheque date:</td>
            <td style="border:1px solid #000;">
                {{ $delivery->cheque_date ? \Carbon\Carbon::parse($delivery->cheque_date)->format('d M Y') : '' }}
            </td>
        </tr>
    </table>
    @endif
    <table width="100%" cellpadding="2" cellspacing="0"
        style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <th colspan="2" style="border:1px solid #000; text-align:center;">
                Receipt Information
            </th>
        </tr>
        <tr>
            <td style="border:1px solid #000; width:30%;">Receipt Date:</td>
            <td style="border:1px solid #000;">
                {{ \Carbon\Carbon::parse($delivery->recipt_date)->format('d M Y') }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Receipt Number:</td>
            <td style="border:1px solid #000;">
                {{ $delivery->recipt_no ?? '' }}
            </td>
        </tr>
    </table>

    <span style="font-weight:bolder;">
        Advance Payment Value
    </span>

    <table width="100%" cellpadding="2" cellspacing="0">
        <tr>
            <td colspan="2" style="text-align:center; font-size:12px;">
                <span style="font-weight:bolder;">
                    This is a system generated advance payment note and doesn't require any signature
                    <br><br>
                </span>
                Thank you for your business
            </td>
        </tr>
    </table>

</body>

</html>