<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repayment Schedule Created</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 32px 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 32px 24px;
        }
        .info-card {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 24px;
            margin: 24px 0;
        }
        .info-title {
            font-weight: 600;
            color: #166534;
            margin-bottom: 8px;
            font-size: 18px;
        }
        .info-text {
            color: #166534;
            font-size: 16px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin: 24px 0;
        }
        .info-item {
            background-color: #f8fafc;
            padding: 16px;
            border-radius: 6px;
            border-left: 4px solid #10b981;
        }
        .info-label {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
        }
        .cta-button {
            display: inline-block;
            background-color: #10b981;
            color: white !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            margin: 24px 0;
        }
        .footer {
            background-color: #f8fafc;
            padding: 24px;
            text-align: center;
            color: #64748b;
            font-size: 14px;
        }
        .footer a {
            color: #10b981;
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Repayment Schedule Created</h1>
        </div>

        <div class="content">
            <p>Dear {{ $invoice->supplier->company_name ?? 'Valued Customer' }},</p>

            <p>We are pleased to inform you that a repayment schedule has been created for your invoice #{{ $invoice->invoice_number ?? $invoice->id }}.</p>

            <div class="info-card">
                <div class="info-title">Schedule Overview</div>
                <div class="info-text">Your invoice total of <strong>{{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}</strong> will be repaid in <strong>{{ $parts }} installments</strong>.</div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Invoice ID</div>
                    <div class="info-value">#{{ $invoice->invoice_number ?? $invoice->id }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Installments</div>
                    <div class="info-value">{{ $parts }} Parts</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Buyer</div>
                    <div class="info-value">{{ $invoice->buyer->name ?? 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Due Date Start</div>
                    <div class="info-value">{{ $invoice->due_date->format('M d, Y') }}</div>
                </div>
            </div>

            <p>You can view the full repayment schedule and individual installment details in your dashboard.</p>

            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/repayments" class="cta-button">View Repayment Dashboard</a>
            </div>

            <p>If you have any questions regarding this schedule, please reach out to our finance team.</p>

            <p>Best regards,<br>
            The Finance Team</p>
        </div>

        <div class="footer">
            <p>This email was sent to {{ $invoice->supplier->contact_email }} regarding your invoice funding.</p>
            <p>
                <a href="{{ config('app.url') }}">Visit our website</a> |
                <a href="{{ config('app.url') }}/contact">Contact Support</a>
            </p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
