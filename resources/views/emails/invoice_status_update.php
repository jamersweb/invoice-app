<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Status Update</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        .status-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            margin: 24px 0;
            text-align: center;
        }
        .status-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        .status-label {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .status-description {
            color: #64748b;
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
            border-left: 4px solid #3b82f6;
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
        .notes {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 16px;
            margin: 24px 0;
        }
        .notes-title {
            font-weight: 600;
            color: #92400e;
            margin-bottom: 8px;
        }
        .notes-content {
            color: #92400e;
        }
        .cta-button {
            display: inline-block;
            background-color: #3b82f6;
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
            color: #3b82f6;
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            .container {
                margin: 0;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Invoice Status Update</h1>
        </div>

        <div class="content">
            <p>Dear {{ $invoice->supplier->company_name ?? 'Valued Customer' }},</p>

            <p>We wanted to inform you about an update to your invoice status.</p>

            <div class="status-card">
                <div class="status-icon">{{ $statusIcon }}</div>
                <div class="status-label">{{ $statusLabel }}</div>
                <div class="status-description">Invoice #{{ $invoice->invoice_number ?? $invoice->id }} has been marked as <strong>{{ $newStatus }}</strong>.</div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Invoice ID</div>
                    <div class="info-value">#{{ $invoice->invoice_number ?? $invoice->id }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Amount</div>
                    <div class="info-value">{{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Previous Status</div>
                    <div class="info-value">{{ ucfirst(str_replace('_', ' ', $oldStatus)) }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Updated On</div>
                    <div class="info-value">{{ now()->format('M d, Y H:i') }}</div>
                </div>
            </div>

            @if($invoice->review_notes)
            <div class="notes">
                <div class="notes-title">Feedback/Notes:</div>
                <div class="notes-content">{{ $invoice->review_notes }}</div>
            </div>
            @endif

            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/invoices/{{ $invoice->id }}" class="cta-button">View Invoice Details</a>
            </div>

            <p>If you have any questions or need assistance, please contact our support team.</p>

            <p>Best regards,<br>
            The Operations Team</p>
        </div>

        <div class="footer">
            <p>This email was sent to {{ $invoice->supplier->contact_email }} regarding your invoice.</p>
            <p>
                <a href="{{ config('app.url') }}">Visit our website</a> |
                <a href="{{ config('app.url') }}/contact">Contact Support</a>
            </p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
