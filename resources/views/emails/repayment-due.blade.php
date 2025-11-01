<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $locale === 'ar' ? 'تنبيه: تاريخ استحقاق السداد قريب' : 'Reminder: Repayment Due Soon' }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb;">
            {{ $locale === 'ar' ? 'تنبيه: تاريخ استحقاق السداد قريب' : 'Reminder: Repayment Due Soon' }}
        </h2>
        
        @if($locale === 'ar')
            <p>مرحباً،</p>
            <p>نود أن نذكرك بأن السداد التالي سيستحق قريباً:</p>
        @else
            <p>Hello,</p>
            <p>We would like to remind you that the following repayment is due soon:</p>
        @endif

        <div style="background: #f3f4f6; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p><strong>{{ $locale === 'ar' ? 'رقم الفاتورة:' : 'Invoice Number:' }}</strong> {{ $repayment->invoice->invoice_number }}</p>
            <p><strong>{{ $locale === 'ar' ? 'المبلغ المستحق:' : 'Amount Due:' }}</strong> {{ number_format($repayment->amount, 2) }} {{ $repayment->invoice->currency }}</p>
            <p><strong>{{ $locale === 'ar' ? 'تاريخ الاستحقاق:' : 'Due Date:' }}</strong> {{ $repayment->due_date->format('Y-m-d') }}</p>
            <p><strong>{{ $locale === 'ar' ? 'الحالة:' : 'Status:' }}</strong> {{ $repayment->status }}</p>
        </div>

        @if($locale === 'ar')
            <p>يرجى التأكد من السداد قبل تاريخ الاستحقاق.</p>
            <p>شكراً لاستخدامكم خدماتنا.</p>
        @else
            <p>Please ensure payment is made before the due date.</p>
            <p>Thank you for using our services.</p>
        @endif
    </div>
</body>
</html>






