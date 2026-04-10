<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Exchange {{ $exchange->exchange_code }}</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; font-size:14px;">
    @php
        $regions = [
            1 => 'Near By Expiry',
            2 => 'Package Issue',
            3 => 'Damage',
            4 => 'Expiry',
            5 => 'Other'
        ];
    @endphp
    <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <td style="border:1px solid #000; text-align:center; color:red; font-size:22px; font-weight:bold;">
                EXCHANGE
                <div style="font-size:14px; margin-top:4px; color:black;">
                    {{ $exchange->exchange_code }}
                </div>
            </td>
        </tr>
    </table>
    <table width="100%" cellpadding="2" cellspacing="0" style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <th colspan="2" style="border:1px solid #000; text-align:center;">Seller's Detail</th>
        </tr>
        <tr>
            <td style="border:1px solid #000; width:30%;">Agent Name:</td>
            <td style="border:1px solid #000;">
                {{ $exchange->warehouse->warehouse_code ?? '' }} - {{ $exchange->warehouse->warehouse_name ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Contact No:</td>
            <td style="border:1px solid #000;">{{ $exchange->warehouse->owner_number ?? '' }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Address:</td>
            <td style="border:1px solid #000;">{{ $exchange->warehouse->city ?? '' }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">TIN No:</td>
            <td style="border:1px solid #000;">{{ $exchange->warehouse->tin_no ?? ''}}</td>
        </tr>
    </table>
    <table width="100%" cellpadding="2" cellspacing="0" style="border-collapse:collapse; border:1px solid #000;">
        <tr>
            <th colspan="2" style="border:1px solid #000; text-align:center;">
                Customer Information
            </th>
        </tr>
        <tr>
            <td style="border:1px solid #000; width:30%;">Customer:</td>
            <td style="border:1px solid #000;">
                {{ $exchange->customer->osa_code ?? '' }} - {{ $exchange->customer->name ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Address:</td>
            <td style="border:1px solid #000;">
                {{ $exchange->customer->street ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid #000;">Contact No:</td>
            <td style="border:1px solid #000;">
                {{ $exchange->customer->contact_no ?? '' }}
            </td>
        </tr>
    </table>
    <table width="100%" cellpadding="2" cellspacing="0" style="border-collapse:collapse;">
        <tr>
            <th colspan="8" style="text-align:center;">Received Item</th>
        </tr>
        <tr>
            <th style="border:1px solid #000;">S/N</th>
            <th style="border:1px solid #000;">Item</th>
            <th style="border:1px solid #000;">UOM</th>
            <th style="border:1px solid #000;">Qty</th>
            <th style="border:1px solid #000;">Price</th>
            <th style="border:1px solid #000;">Total</th>
            <th style="border:1px solid #000;">Return Type</th>
            <th style="border:1px solid #000;">Return Reason</th>
        </tr>
        @foreach($returnItems as $i => $item)
        <tr>
            <td style="border:1px solid #000; text-align:center;">{{ $i+1 }}</td>
            <td style="border:1px solid #000;">{{ $item->item->name ?? '' }}</td>
            <td style="border:1px solid #000; text-align:center;">
                {{ $item->inuoms->name ?? $item->uoms->name ?? '' }}
            </td>
            <td style="border:1px solid #000; text-align:center;">{{ $item->item_quantity }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->item_price, 2) }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->total, 2) }}</td>

            <td style="border:1px solid #000; text-align:right;">
                {{ (string)$item->return_type === '1' ? 'Good' : 'Bad' }}
            </td>

            <td style="border:1px solid #000; text-align:right;">
                {{ $regions[(int)$item->region] ?? 'Unknown' }}
            </td>
        </tr>
        @endforeach
    </table>

     <table width="100%" cellpadding="2" cellspacing="0" style="border-collapse:collapse;">
        <tr>
            <th colspan="6" style="text-align:center;">Delivered Items</th>
        </tr>
        <tr>
            <th style="border:1px solid #000;">S/N</th>
            <th style="border:1px solid #000;">Item</th>
            <th style="border:1px solid #000;">UOM</th>
            <th style="border:1px solid #000;">Qty</th>
            <th style="border:1px solid #000;">Price</th>
            <th style="border:1px solid #000;">Total</th>
        </tr>
        @foreach($returnItems as $i => $item)
        <tr>
            <td style="border:1px solid #000; text-align:center;">{{ $i+1 }}</td>
            <td style="border:1px solid #000;">{{ $item->item->name ?? '' }}</td>
            <td style="border:1px solid #000; text-align:center;">{{ $item->inuoms->name ?? $item->uoms->name ?? '' }}</td>
            <td style="border:1px solid #000; text-align:center;">{{ $item->item_quantity }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->item_price, 2) }}</td>
            <td style="border:1px solid #000; text-align:right;">{{ number_format($item->total, 2) }}</td>
        </tr>
        @endforeach
    </table>

    
    <table width="100%" cellpadding="2" cellspacing="0">
        <tr>
            <td style="text-align:center; font-size:12px;">
                <strong>This is a system generated exchange document</strong>
            </td>
        </tr>
    </table>
</body>
</html>