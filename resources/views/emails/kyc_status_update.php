<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Status Update</title>
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
            color: white;
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
            <h1>KYC Application Status Update</h1>
        </div>

        <div class="content">
            <p>Dear {{ $supplier->company_name ?? 'Valued Customer' }},</p>

            <p>We wanted to inform you about an update to your KYC (Know Your Customer) application status.</p>

            <div class="status-card">
                <div class="status-icon">{{ $statusConfig[$newStatus]['icon'] }}</div>
                <div class="status-label">{{ $statusConfig[$newStatus]['label'] }}</div>
                <div class="status-description">{{ $statusConfig[$newStatus]['description'] }}</div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Application ID</div>
                    <div class="info-value">#{{ $supplier->id }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Company Name</div>
                    <div class="info-value">{{ $supplier->company_name ?? 'Not provided' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Previous Status</div>
                    <div class="info-value">{{ $statusConfig[$oldStatus]['label'] }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Updated On</div>
                    <div class="info-value">{{ now()->format('M d, Y') }}</div>
                </div>
            </div>

            @if($notes)
            <div class="notes">
                <div class="notes-title">Additional Information:</div>
                <div class="notes-content">{{ $notes }}</div>
            </div>
            @endif

            @if($newStatus === 'approved')
            <p>Congratulations! Your application has been approved and you now have full access to our platform services. You can:</p>
            <ul>
                <li>Submit invoices for funding</li>
                <li>Access your dashboard</li>
                <li>View your account status</li>
                <li>Contact our support team for assistance</li>
            </ul>
            @elseif($newStatus === 'rejected')
            <p>To proceed with your application, please:</p>
            <ul>
                <li>Review the feedback provided above</li>
                <li>Update your application with the required information</li>
                <li>Resubmit your documents if needed</li>
                <li>Contact our support team if you have questions</li>
            </ul>
            @elseif($newStatus === 'under_review')
            <p>Our compliance team is currently reviewing your application. This process typically takes 2-3 business days. We will notify you once the review is complete.</p>
            @endif

            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/kyc-status" class="cta-button">View Application Status</a>
            </div>

            <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>

            <p>Best regards,<br>
            The Compliance Team</p>
        </div>

        <div class="footer">
            <p>This email was sent to {{ $supplier->contact_email }} regarding your KYC application.</p>
            <p>
                <a href="{{ config('app.url') }}">Visit our website</a> |
                <a href="{{ config('app.url') }}/contact">Contact Support</a>
            </p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
