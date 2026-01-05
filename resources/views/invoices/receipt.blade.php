<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 14px; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 40px 30px; }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 3px solid #10b981; padding-bottom: 20px; }
        .receipt-title { font-size: 32px; font-weight: bold; color: #10b981; margin-bottom: 10px; }
        .receipt-subtitle { font-size: 14px; color: #6b7280; }
        .section { margin-bottom: 30px; }
        .section-title { font-size: 16px; font-weight: bold; margin-bottom: 15px; color: #1f2937; border-bottom: 2px solid #e5e7eb; padding-bottom: 5px; }
        .info-row { padding: 8px 0; border-bottom: 1px solid #f3f4f6; display: table; width: 100%; }
        .info-label { font-weight: bold; color: #4b5563; display: table-cell; width: 40%; }
        .info-value { color: #1f2937; display: table-cell; }
        .amount-box { background-color: #d1fae5; padding: 20px; border-radius: 8px; text-align: center; margin: 30px 0; }
        .amount-label { font-size: 14px; color: #065f46; font-weight: bold; margin-bottom: 5px; }
        .amount-value { font-size: 32px; font-weight: bold; color: #065f46; }
        .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #6b7280; border-top: 2px solid #e5e7eb; padding-top: 20px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: bold; background-color: #d1fae5; color: #065f46; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="receipt-title">PAYMENT RECEIPT</div>
            <div class="receipt-subtitle">{{ $company['name'] }}</div>
        </div>

        <div class="amount-box">
            <div class="amount-label">AMOUNT PAID</div>
            <div class="amount-value">ZMW {{ number_format($payment->amount, 2) }}</div>
        </div>

        <div class="section">
            <div class="section-title">Payment Details</div>
            <div class="info-row">
                <span class="info-label">Receipt #:</span>
                <span class="info-value">{{ $receipt['number'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Transaction ID:</span>
                <span class="info-value">{{ $payment->transaction_id }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Payment Date:</span>
                <span class="info-value">{{ $receipt['date'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Payment Method:</span>
                <span class="info-value">{{ $payment->payment_method }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value"><span class="badge">PAID</span></span>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Booking Information</div>
            <div class="info-row">
                <span class="info-label">Booking #:</span>
                <span class="info-value">{{ $booking->booking_number }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Accommodation:</span>
                <span class="info-value">{{ $booking->accommodation->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Guest Name:</span>
                <span class="info-value">{{ $booking->guest_first_name }} {{ $booking->guest_last_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Check-in:</span>
                <span class="info-value">{{ $booking->check_in_date->format('M d, Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Check-out:</span>
                <span class="info-value">{{ $booking->check_out_date->format('M d, Y') }}</span>
            </div>
        </div>

        <div class="footer">
            <p><strong>Thank you for your payment!</strong></p>
            <p>For inquiries, contact us at {{ $company['email'] }} or {{ $company['phone'] }}</p>
            <p style="margin-top: 10px; font-size: 11px;">This is an official payment receipt.</p>
        </div>
    </div>
</body>
</html>
