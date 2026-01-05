<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice['number'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 30px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 40px;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 20px;
        }
        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 10px;
        }
        .company-details {
            font-size: 12px;
            color: #666;
            line-height: 1.8;
        }
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }
        .invoice-meta {
            font-size: 12px;
            color: #666;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }
        .info-table {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .info-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }
        .info-row {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #4b5563;
            display: inline-block;
            min-width: 120px;
        }
        .info-value {
            color: #1f2937;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table thead {
            background-color: #f3f4f6;
        }
        table th {
            padding: 12px 10px;
            text-align: left;
            font-weight: bold;
            color: #374151;
            border-bottom: 2px solid #d1d5db;
        }
        table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        table tbody tr:last-child td {
            border-bottom: none;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .item-description {
            font-weight: 500;
            color: #1f2937;
        }
        .item-details {
            font-size: 12px;
            color: #6b7280;
            margin-top: 3px;
        }
        .totals-table {
            width: 350px;
            margin-left: auto;
            margin-top: 20px;
        }
        .totals-table td {
            padding: 8px 10px;
            border: none;
        }
        .totals-table .total-row td {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
            padding-top: 15px;
            border-top: 2px solid #3b82f6;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-paid {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .badge-confirmed {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }
        .notes {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin-top: 30px;
            font-size: 12px;
            color: #4b5563;
        }
        .notes-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="logo">{{ $company['name'] }}</div>
                <div class="company-details">
                    {{ $company['address'] }}<br>
                    Phone: {{ $company['phone'] }}<br>
                    Email: {{ $company['email'] }}<br>
                    {{ $company['website'] }}
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-meta">
                    <strong>Invoice #:</strong> {{ $invoice['number'] }}<br>
                    <strong>Date:</strong> {{ $invoice['date'] }}<br>
                    <strong>Due Date:</strong> {{ $invoice['due_date'] }}
                </div>
            </div>
        </div>

        <!-- Bill To & Booking Info -->
        <div class="info-table">
            <div class="info-column">
                <div class="section-title">Bill To</div>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value">{{ $customer['name'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $customer['email'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span class="info-value">{{ $customer['phone'] }}</span>
                </div>
            </div>
            <div class="info-column">
                <div class="section-title">Booking Details</div>
                <div class="info-row">
                    <span class="info-label">Booking #:</span>
                    <span class="info-value">{{ $booking->booking_number }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Check-in:</span>
                    <span class="info-value">{{ $booking->check_in_date->format('M d, Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Check-out:</span>
                    <span class="info-value">{{ $booking->check_out_date->format('M d, Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Guests:</span>
                    <span class="info-value">{{ $booking->num_guests }} ({{ $booking->num_adults }} adults, {{ $booking->num_children }} children)</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="badge badge-confirmed">{{ $booking_status }}</span>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="section">
            <div class="section-title">Items</div>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>
                            <div class="item-description">{{ $item['description'] }}</div>
                            <div class="item-details">{{ $item['details'] }}</div>
                        </td>
                        <td class="text-center">{{ $item['quantity'] }}</td>
                        <td class="text-right">ZMW {{ number_format($item['unit_price'], 2) }}</td>
                        <td class="text-right">ZMW {{ number_format($item['amount'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <table class="totals-table">
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">ZMW {{ number_format($totals['subtotal'], 2) }}</td>
            </tr>
            @if($totals['discount'] > 0)
            <tr>
                <td>Discount:</td>
                <td class="text-right">- ZMW {{ number_format($totals['discount'], 2) }}</td>
            </tr>
            @endif
            <tr>
                <td>Tax (18%):</td>
                <td class="text-right">ZMW {{ number_format($totals['tax'], 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>TOTAL:</td>
                <td class="text-right">ZMW {{ number_format($totals['total'], 2) }}</td>
            </tr>
            <tr>
                <td>Payment Status:</td>
                <td class="text-right">
                    <span class="badge badge-{{ strtolower($payment_status) === 'paid' ? 'paid' : 'pending' }}">
                        {{ $payment_status }}
                    </span>
                </td>
            </tr>
        </table>

        <!-- Notes -->
        @if($booking->special_requests)
        <div class="notes">
            <div class="notes-title">Special Requests:</div>
            {{ $booking->special_requests }}
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for choosing {{ $company['name'] }}!</p>
            <p>If you have any questions about this invoice, please contact us at {{ $company['email'] }}</p>
            <p style="margin-top: 10px; font-size: 11px;">This is a computer-generated invoice and does not require a signature.</p>
        </div>
    </div>
</body>
</html>
