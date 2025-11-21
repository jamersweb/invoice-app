<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $locale === 'ar' ? 'إشعار: فواتير جديدة' : 'New Invoices Notification' }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb;">
            {{ $locale === 'ar' ? 'إشعار: فواتير جديدة' : 'New Invoices Submitted' }}
        </h2>
        
        @if($locale === 'ar')
            <p>مرحباً،</p>
            <p>تم تقديم <strong>{{ $count }}</strong> فاتورة جديدة من قبل المورد:</p>
            <p><strong>{{ $supplier->company_name ?? $supplier->legal_name }}</strong></p>
        @else
            <p>Hello,</p>
            <p><strong>{{ $count }}</strong> new invoice(s) have been submitted by supplier:</p>
            <p><strong>{{ $supplier->company_name ?? $supplier->legal_name }}</strong></p>
        @endif

        <div style="background: #f3f4f6; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0;">{{ $locale === 'ar' ? 'تفاصيل الفواتير:' : 'Invoice Details:' }}</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #e5e7eb;">
                        <th style="padding: 8px; text-align: left; border: 1px solid #d1d5db;">{{ $locale === 'ar' ? 'رقم الفاتورة' : 'Invoice #' }}</th>
                        <th style="padding: 8px; text-align: left; border: 1px solid #d1d5db;">{{ $locale === 'ar' ? 'المبلغ' : 'Amount' }}</th>
                        <th style="padding: 8px; text-align: left; border: 1px solid #d1d5db;">{{ $locale === 'ar' ? 'تاريخ الاستحقاق' : 'Due Date' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                    <tr>
                        <td style="padding: 8px; border: 1px solid #d1d5db;">{{ $invoice->invoice_number }}</td>
                        <td style="padding: 8px; border: 1px solid #d1d5db;">{{ number_format($invoice->amount, 2) }} {{ $invoice->currency }}</td>
                        <td style="padding: 8px; border: 1px solid #d1d5db;">{{ $invoice->due_date->format('Y-m-d') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px; padding: 15px; background: #dbeafe; border-radius: 5px;">
            <p style="margin: 0;">
                <a href="{{ route('admin.invoice-review') }}" style="color: #1e40af; text-decoration: none; font-weight: bold;">
                    {{ $locale === 'ar' ? 'مراجعة الفواتير' : 'Review Invoices' }}
                </a>
            </p>
        </div>

        @if($locale === 'ar')
            <p style="margin-top: 20px; color: #6b7280; font-size: 12px;">
                هذه رسالة تلقائية من نظام إدارة الفواتير.
            </p>
        @else
            <p style="margin-top: 20px; color: #6b7280; font-size: 12px;">
                This is an automated message from the Invoice Management System.
            </p>
        @endif
    </div>
</body>
</html>

