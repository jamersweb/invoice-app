<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\AgreementTemplate;
use App\Jobs\Agreements\GenerateAgreementPdf;
use Illuminate\Support\Facades\Mail;

class ContractService
{
    /**
     * Create a new agreement draft from a template with variables.
     */
    public function createDraft(int $templateId, int $userId, array $variables, ?int $invoiceId = null): Agreement
    {
        $template = AgreementTemplate::findOrFail($templateId);
        
        $supplierId = null;
        if ($invoiceId) {
            $invoice = \App\Modules\Invoices\Models\Invoice::find($invoiceId);
            $supplierId = $invoice?->supplier_id;
        }

        $agreement = Agreement::create([
            'agreement_template_id' => $templateId,
            'invoice_id' => $invoiceId,
            'supplier_id' => $supplierId,
            'version' => $template->version ?? '1.0',
            'signer_id' => $userId,
            'terms_snapshot_json' => $variables,
            'status' => 'Draft',
        ]);

        // Trigger PDF generation job
        GenerateAgreementPdf::dispatch($agreement);

        return $agreement;
    }

    /**
     * Replace placeholders in HTML with variables.
     */
    public function processTemplate(string $html, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $html = str_replace('{{' . $key . '}}', $value, $html);
        }
        return $html;
    }

    /**
     * Send contract to customer notification.
     */
    public function sendToCustomer(Agreement $agreement)
    {
        $agreement->update(['status' => 'Sent']);

        // Notification logic
        $supplier = $agreement->invoice?->supplier;
        if (!$supplier) {
            // Fallback: If no invoice is linked, try to find supplier by signer
            $user = \App\Models\User::find($agreement->signer_id);
            if ($user) {
                $supplier = \App\Models\Supplier::where('contact_email', $user->email)->first();
            }
        }

        if ($supplier && $supplier->contact_email) {
            Mail::to($supplier->contact_email)->send(new \App\Mail\ContractSentMail($agreement));
        }
    }

    /**
     * Mark contract as signed and update deal status.
     */
    public function sign(Agreement $agreement)
    {
        $agreement->update([
            'status' => 'Signed',
            'signed_at' => now(),
        ]);

        // If it's linked to an invoice/deal
        if ($agreement->terms_snapshot_json && isset($agreement->terms_snapshot_json['invoice_id'])) {
            $invoice = \App\Modules\Invoices\Models\Invoice::find($agreement->terms_snapshot_json['invoice_id']);
            if ($invoice) {
                $invoice->update(['status' => 'contracted']);
            }
        }
    }
}
