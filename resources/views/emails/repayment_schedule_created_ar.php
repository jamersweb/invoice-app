<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تم إنشاء جدول السداد</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
            text-align: right;
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
            border-right: 4px solid #10b981;
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
            <h1>تم إنشاء جدول السداد</h1>
        </div>

        <div class="content">
            <p>عزيزنا {{ $invoice->supplier->company_name ?? 'العميل العزيز' }}،</p>

            <p>يسعدنا إبلاغكم بأنه تم إنشاء جدول سداد لفاتورتكم رقم #{{ $invoice->invoice_number ?? $invoice->id }}.</p>

            <div class="info-card">
                <div class="info-title">نظرة عامة على الجدول</div>
                <div class="info-text">سيتم سداد إجمالي فاتورتكم البالغ <strong>{{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}</strong> على <strong>{{ $parts }} أقساط</strong>.</div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">رقم الفاتورة</div>
                    <div class="info-value">#{{ $invoice->invoice_number ?? $invoice->id }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">عدد الأقساط</div>
                    <div class="info-value">{{ $parts }} أجزاء</div>
                </div>
                <div class="info-item">
                    <div class="info-label">المشتري</div>
                    <div class="info-value">{{ $invoice->buyer->name ?? 'غير متوفر' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">تاريخ الاستحقاق</div>
                    <div class="info-value">{{ $invoice->due_date->format('Y-m-d') }}</div>
                </div>
            </div>

            <p>يمكنكم عرض جدول السداد الكامل وتفاصيل الأقساط الفردية في لوحة التحكم الخاصة بكم.</p>

            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/repayments" class="cta-button">عرض لوحة تحكم السداد</a>
            </div>

            <p>إذا كان لديك أي أسئلة بخصوص هذا الجدول، يرجى التواصل مع فريقنا المالي.</p>

            <p>مع أطيب التحيات،<br>
            الفريق المالي</p>
        </div>

        <div class="footer">
            <p>تم إرسال هذا البريد الإلكتروني إلى {{ $invoice->supplier->contact_email }} بخصوص تمويل فاتورتكم.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</body>
</html>
