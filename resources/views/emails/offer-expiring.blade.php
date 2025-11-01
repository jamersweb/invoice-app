<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $locale === 'ar' ? 'عرضك على وشك الانتهاء' : 'Your Offer is Expiring' }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb;">
            {{ $locale === 'ar' ? 'تنبيه: عرضك على وشك الانتهاء' : 'Reminder: Your Offer is Expiring Soon' }}
        </h2>
        
        @if($locale === 'ar')
            <p>مرحباً،</p>
            <p>نود أن نذكرك بأن عرضك التالي على وشك الانتهاء:</p>
        @else
            <p>Hello,</p>
            <p>We would like to remind you that your following offer is about to expire:</p>
        @endif

        <div style="background: #f3f4f6; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p><strong>{{ $locale === 'ar' ? 'رقم الفاتورة:' : 'Invoice Number:' }}</strong> {{ $offer->invoice->invoice_number }}</p>
            <p><strong>{{ $locale === 'ar' ? 'المبلغ:' : 'Amount:' }}</strong> {{ number_format($offer->amount, 2) }} {{ $offer->invoice->currency }}</p>
            <p><strong>{{ $locale === 'ar' ? 'المبلغ الصافي:' : 'Net Amount:' }}</strong> {{ number_format($offer->net_amount ?? $offer->amount, 2) }} {{ $offer->invoice->currency }}</p>
            <p><strong>{{ $locale === 'ar' ? 'تاريخ الانتهاء:' : 'Expires At:' }}</strong> {{ $offer->expires_at->format('Y-m-d H:i') }}</p>
        </div>

        @if($locale === 'ar')
            <p>يرجى مراجعة العرض والرد قبل تاريخ الانتهاء.</p>
            <p>شكراً لاستخدامكم خدماتنا.</p>
        @else
            <p>Please review and respond to the offer before it expires.</p>
            <p>Thank you for using our services.</p>
        @endif
    </div>
</body>
</html>






