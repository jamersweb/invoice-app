<?php

namespace App\Jobs\Agreements;

use App\Models\Agreement;
use App\Models\AgreementTemplate;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Storage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateAgreementPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Agreement $agreement)
    {
    }

    public function handle(): void
    {
        $template = AgreementTemplate::findOrFail($this->agreement->agreement_template_id);
        $html = $template->html;

        // Replace variables if present
        if ($this->agreement->terms_snapshot_json) {
            $service = new \App\Services\ContractService();
            $html = $service->processTemplate($html, $this->agreement->terms_snapshot_json);
        }

        $pdf = PDF::loadHTML($html);
        $path = 'agreements/' . $this->agreement->id . '-draft.pdf';
        Storage::disk('public')->put($path, $pdf->output());
        $this->agreement->update(['signed_pdf' => $path]);
    }
}


