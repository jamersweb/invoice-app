<?php

namespace App\Mail;

use App\Models\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KycStatusUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Supplier $supplier,
        public string $oldStatus,
        public string $newStatus,
        public ?string $notes = null
    ) {}

    public function envelope(): Envelope
    {
        $subject = match($this->newStatus) {
            'under_review' => 'Your KYC Application is Under Review',
            'approved' => 'Congratulations! Your KYC Application is Approved',
            'rejected' => 'KYC Application Update Required',
            default => 'KYC Application Status Update'
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        $locale = app()->getLocale();
        $view = $locale === 'ar' ? 'emails.kyc_status_update_ar' : 'emails.kyc_status_update';

        return new Content(
            view: $view,
            with: [
                'supplier' => $this->supplier,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
                'notes' => $this->notes,
                'statusConfig' => $this->getStatusConfig(),
            ]
        );
    }

    private function getStatusConfig(): array
    {
        return [
            'pending' => [
                'label' => 'Pending Review',
                'description' => 'Your application is waiting to be reviewed by our compliance team.',
                'color' => 'yellow',
                'icon' => '⏳'
            ],
            'under_review' => [
                'label' => 'Under Review',
                'description' => 'Our team is currently reviewing your application and documents.',
                'color' => 'blue',
                'icon' => '🔍'
            ],
            'approved' => [
                'label' => 'Approved',
                'description' => 'Congratulations! Your application has been approved and you can now access our services.',
                'color' => 'green',
                'icon' => '✅'
            ],
            'rejected' => [
                'label' => 'Rejected',
                'description' => 'Your application requires additional information or corrections.',
                'color' => 'red',
                'icon' => '❌'
            ]
        ];
    }
}
