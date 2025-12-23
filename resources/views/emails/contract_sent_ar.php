<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اتفاقية للمراجعة</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #374151; background-color: #f9fafb; margin: 0; padding: 0; text-align: right; }
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
            <h1>اتفاقية للمراجعة</h1>
        </div>
        <div class="content">
            <p>عزيزنا {{ $supplierName }}،</p>
            <p>تم إعداد اتفاقية جديدة لمراجعتكم وتوقيعكم بخصوص طلب تمويل الفاتورة الخاص بكم.</p>
            <p>يرجى النقر على الزر أدناه لعرض الاتفاقية وإكمال عملية التوقيع الرقمي.</p>
            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/agreements" class="cta-button">عرض وتوقيع الاتفاقية</a>
            </div>
            <p>إذا كان لديك أي أسئلة، يرجى عدم التردد في التواصل مع فريقنا.</p>
            <p>مع أطيب التحيات،<br>فريق العمليات</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</body>
</html>
