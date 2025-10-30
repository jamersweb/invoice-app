<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.collections_reminder_subject') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #dc3545;">{{ __('messages.collections_reminder_subject') }}</h2>

        <p>{{ __('messages.collections_reminder_greeting') }}</p>

        <div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0;">
            <h3 style="margin-top: 0;">{{ __('messages.invoice_details') }}</h3>
            <p><strong>{{ __('messages.invoice_number') }}:</strong> {{ $invoice->invoice_number }}</p>
            <p><strong>{{ __('messages.amount') }}:</strong> ${{ $amount }}</p>
            <p><strong>{{ __('messages.due_date') }}:</strong> {{ $due_date }}</p>
            <p><strong>{{ __('messages.days_overdue') }}:</strong> {{ $days_overdue }} {{ __('messages.days') }}</p>
        </div>

        <p>{{ __('messages.collections_reminder_message') }}</p>

        <div style="margin: 30px 0;">
            <a href="{{ config('app.url') }}/invoices/{{ $invoice->id }}"
               style="background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
                {{ __('messages.view_invoice') }}
            </a>
        </div>

        <p style="font-size: 14px; color: #666;">
            {{ __('messages.collections_reminder_footer') }}
        </p>

        <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
        <p style="font-size: 12px; color: #999;">
            {{ __('messages.email_footer') }}
        </p>
    </div>
</body>
</html>
