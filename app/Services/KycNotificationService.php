<?php

namespace App\Services;

use App\Models\Supplier;
use App\Mail\KycStatusUpdateMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class KycNotificationService
{
    public function sendStatusUpdateNotification(
        Supplier $supplier,
        string $oldStatus,
        string $newStatus,
        ?string $notes = null
    ): bool {
        try {
            // Determine the recipient's preferred language
            $locale = $this->determineLocale($supplier);

            // Set the application locale for email content
            app()->setLocale($locale);

            // Send the email
            Mail::to($supplier->contact_email)
                ->send(new KycStatusUpdateMail($supplier, $oldStatus, $newStatus, $notes));

            // Log the notification
            Log::info('KYC status update notification sent', [
                'supplier_id' => $supplier->id,
                'email' => $supplier->contact_email,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'locale' => $locale
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send KYC status update notification', [
                'supplier_id' => $supplier->id,
                'email' => $supplier->contact_email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function sendWelcomeNotification(Supplier $supplier): bool
    {
        try {
            $locale = $this->determineLocale($supplier);
            app()->setLocale($locale);

            // Use existing welcome mail class
            $welcomeMail = new \App\Mail\SupplierWelcomeMail($supplier);
            Mail::to($supplier->contact_email)->send($welcomeMail);

            Log::info('KYC welcome notification sent', [
                'supplier_id' => $supplier->id,
                'email' => $supplier->contact_email,
                'locale' => $locale
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send KYC welcome notification', [
                'supplier_id' => $supplier->id,
                'email' => $supplier->contact_email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function sendDocumentExpiryReminder(Supplier $supplier, array $expiringDocuments): bool
    {
        try {
            $locale = $this->determineLocale($supplier);
            app()->setLocale($locale);

            // Use existing document expiry mail class
            $expiryMail = new \App\Mail\DocumentExpiryMail($supplier, $expiringDocuments);
            Mail::to($supplier->contact_email)->send($expiryMail);

            Log::info('Document expiry reminder sent', [
                'supplier_id' => $supplier->id,
                'email' => $supplier->contact_email,
                'expiring_documents_count' => count($expiringDocuments),
                'locale' => $locale
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send document expiry reminder', [
                'supplier_id' => $supplier->id,
                'email' => $supplier->contact_email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    private function determineLocale(Supplier $supplier): string
    {
        // Check if supplier has a preferred language setting
        if (isset($supplier->kyc_data['preferred_language'])) {
            return $supplier->kyc_data['preferred_language'];
        }

        // Check country-based locale mapping
        $countryLocaleMap = [
            'SA' => 'ar', // Saudi Arabia
            'AE' => 'ar', // UAE
            'KW' => 'ar', // Kuwait
            'QA' => 'ar', // Qatar
            'BH' => 'ar', // Bahrain
            'OM' => 'ar', // Oman
            'JO' => 'ar', // Jordan
            'LB' => 'ar', // Lebanon
            'EG' => 'ar', // Egypt
            'MA' => 'ar', // Morocco
            'TN' => 'ar', // Tunisia
            'DZ' => 'ar', // Algeria
            'LY' => 'ar', // Libya
            'SD' => 'ar', // Sudan
            'IQ' => 'ar', // Iraq
            'SY' => 'ar', // Syria
            'YE' => 'ar', // Yemen
        ];

        if ($supplier->country && isset($countryLocaleMap[$supplier->country])) {
            return $countryLocaleMap[$supplier->country];
        }

        // Default to English
        return 'en';
    }
}
