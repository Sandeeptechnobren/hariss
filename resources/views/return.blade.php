<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Return #{{ $return->uuid }}</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #333;
            padding: 30px;
        }

        .invoice-container {
            background: #fff;
            margin: auto;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
        }

        /* ================= HEADER ================= */
        header {
            padding: 20px;
            border-bottom: 1px solid #dcdcdc;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h2 {
            font-size: 22px;
            margin-bottom: 5px;
        }

        .invoice-title span {
            font-size: 12px;
            color: #555;
        }

        /* ================= ADDRESS ================= */
        .address-section {
            display: table;
            width: 95%;
            margin: 20px auto;
            border-spacing: 15px 0;
        }

        .address-box {
            display: table-cell;
            width: 50%;
            padding: 15px;
            background: #fafafa;
            border: 1px solid #e2e2e2;
            vertical-align: top;
            border-radius: 6px;
        }

        .address-box h4 {
            font-size: 14px;
            margin-bottom: 8px;
        }

        .address-box p {
            font-size: 12px;
            line-height: 1.4;
        }

        /* ================= TABLE ================= */
        .table-box {
            width: 95%;
            margin: 0 auto 20px;
            border: 1px solid #cfcfcf;
            border-radius: 10px;
            overflow: hidden;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #f0f0f0;
            font-size: 11px;
            padding: 6px;
            border: 1px solid #d0d0d0;
            text-align: center;
        }

        .data-table td {
            font-size: 11px;
            padding: 6px;
            border: 1px solid #e0e0e0;
            text-align: center;
            white-space: normal;
            vertical-align: middle;
        }

        /* Column width control (PDF safe) */
        .data-table th:nth-child(7),
        .data-table td:nth-child(7) {
            width: 70px;
        }

        .data-table th:nth-child(8),
        .data-table td:nth-child(8) {
            width: 80px;
        }

        .return-reason {
            line-height: 1.2;
        }
    </style>
</head>

<body>

<div class="invoice-container">

    <!-- HEADER -->
    <header>
        <div class="invoice-title">
            <h2>RETURN</h2>
            <span>{{ $return->osa_code }}</span>
        </div>
    </header>

    <!-- ADDRESS -->
    <div class="address-section">
        <div class="address-box">
            <h4>Seller</h4>
            <p>
                <strong>{{ $return->warehouse->warehouse_name ?? '-' }}</strong><br>
                {{ $return->warehouse->city ?? '-' }}<br>
                Phone: {{ $return->warehouse->warehouse_manager_contact ?? '-' }}<br>
                TIN: {{ $return->warehouse->tin_no ?? '-' }}
            </p>
        </div>

        <div class="address-box">
            <h4>Buyer</h4>
            <p>
                <strong>{{ $return->customer->name ?? '-' }}</strong><br>
                {{ $return->customer->district ?? '-' }}<br>
                Phone: {{ $return->customer->contact_no ?? '-' }}<br>
                OSA Code: {{ $return->customer->osa_code ?? '-' }}
            </p>
        </div>
    </div>

    <!-- ITEMS TABLE -->
    <div class="table-box">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>UOM</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Return Type</th>
                    <th>Return Reason</th>
                    <th>Total</th>
                </tr>
            </thead>

            <tbody>
                @forelse($returnDetails as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item->item->code ?? '-' }}</td>
                        <td>{{ $item->item->name ?? '-' }}</td>
                        <td>{{ $item->uom->name ?? '-' }}</td>
                        <td>{{ number_format($item->item_quantity) }}</td>
                        <td>{{ number_format($item->item_price, 2) }}</td>

                        <!-- Return Type -->
                        <td>
                            {{ $item->return_type == 1 ? 'Good' : 'Bad' }}
                        </td>

                        <!-- Return Reason -->
                        <td class="return-reason">
                            @if($item->return_reason == 1)
                                Near By<br>Expiry
                            @elseif($item->return_reason == 2)
                                Package<br>Issue
                            @elseif($item->return_reason == 3)
                                Damage
                            @elseif($item->return_reason == 4)
                                Expiry
                            @else
                                -
                            @endif
                        </td>

                        <td>{{ number_format($item->total, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">No return details found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

</body>
</html>