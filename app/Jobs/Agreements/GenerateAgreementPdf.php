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

    public function __construct(public Agreement $agreement) {}

    public function handle(): void
    {
        $template = AgreementTemplate::findOrFail($this->agreement->agreement_template_id);
        $pdf = PDF::loadHTML($template->html);
        $path = 'agreements/'.$this->agreement->id.'-draft.pdf';
        Storage::disk('public')->put($path, $pdf->output());
        $this->agreement->update(['signed_pdf' => $path]);
    }
}


