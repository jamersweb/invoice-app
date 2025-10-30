<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث حالة التحقق من الهوية</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
            direction: rtl;
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
            border-right: 4px solid #3b82f6;
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
            <h1>تحديث حالة طلب التحقق من الهوية</h1>
        </div>

        <div class="content">
            <p>عزيزي {{ $supplier->company_name ?? 'عميلنا الكريم' }}،</p>

            <p>نود إعلامك بتحديث حالة طلب التحقق من الهوية (KYC) الخاص بك.</p>

            <div class="status-card">
                <div class="status-icon">{{ $statusConfig[$newStatus]['icon'] }}</div>
                <div class="status-label">{{ $statusConfig[$newStatus]['label'] }}</div>
                <div class="status-description">{{ $statusConfig[$newStatus]['description'] }}</div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">رقم الطلب</div>
                    <div class="info-value">#{{ $supplier->id }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">اسم الشركة</div>
                    <div class="info-value">{{ $supplier->company_name ?? 'غير محدد' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">الحالة السابقة</div>
                    <div class="info-value">{{ $statusConfig[$oldStatus]['label'] }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">تاريخ التحديث</div>
                    <div class="info-value">{{ now()->format('M d, Y') }}</div>
                </div>
            </div>

            @if($notes)
            <div class="notes">
                <div class="notes-title">معلومات إضافية:</div>
                <div class="notes-content">{{ $notes }}</div>
            </div>
            @endif

            @if($newStatus === 'approved')
            <p>تهانينا! تم الموافقة على طلبك ولديك الآن وصول كامل لخدمات منصتنا. يمكنك:</p>
            <ul>
                <li>تقديم الفواتير للتمويل</li>
                <li>الوصول إلى لوحة التحكم</li>
                <li>عرض حالة حسابك</li>
                <li>التواصل مع فريق الدعم للحصول على المساعدة</li>
            </ul>
            @elseif($newStatus === 'rejected')
            <p>لمتابعة طلبك، يرجى:</p>
            <ul>
                <li>مراجعة الملاحظات المقدمة أعلاه</li>
                <li>تحديث طلبك بالمعلومات المطلوبة</li>
                <li>إعادة تقديم المستندات إذا لزم الأمر</li>
                <li>التواصل مع فريق الدعم إذا كان لديك أسئلة</li>
            </ul>
            @elseif($newStatus === 'under_review')
            <p>فريق الامتثال لدينا يراجع حالياً طلبك. تستغرق هذه العملية عادة 2-3 أيام عمل. سنقوم بإعلامك بمجرد اكتمال المراجعة.</p>
            @endif

            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/kyc-status" class="cta-button">عرض حالة الطلب</a>
            </div>

            <p>إذا كان لديك أي أسئلة أو تحتاج إلى مساعدة، فلا تتردد في التواصل مع فريق الدعم.</p>

            <p>مع أطيب التحيات،<br>
            فريق الامتثال</p>
        </div>

        <div class="footer">
            <p>تم إرسال هذا البريد الإلكتروني إلى {{ $supplier->contact_email }} بخصوص طلب التحقق من الهوية الخاص بك.</p>
            <p>
                <a href="{{ config('app.url') }}">زيارة موقعنا</a> |
                <a href="{{ config('app.url') }}/contact">اتصل بالدعم</a>
            </p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</body>
</html>
