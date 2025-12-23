<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agreement for Review</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #374151; background-color: #f9fafb; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: white; padding: 32px 24px; text-align: center; }
        .content { padding: 32px 24px; }
        .cta-button { display: inline-block; background-color: #4f46e5; color: white !important; text-decoration: none; padding: 14px 28px; border-radius: 6px; font-weight: 600; margin: 24px 0; }
        .footer { background-color: #f8fafc; padding: 24px; text-align: center; color: #64748b; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Agreement for Your Review</h1>
        </div>
        <div class="content">
            <p>Dear {{ $supplierName }},</p>
            <p>A new agreement has been prepared for your review and signature regarding your invoice funding request.</p>
            <p>Please click the button below to view the agreement and complete the digital signing process.</p>
            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/agreements" class="cta-button">View & Sign Agreement</a>
            </div>
            <p>If you have any questions, please don't hesitate to reach out to our team.</p>
            <p>Best regards,<br>The Operations Team</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
