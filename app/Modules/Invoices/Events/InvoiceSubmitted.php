<?php

namespace App\Modules\Invoices\Events;

use App\Modules\Invoices\Models\Invoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Invoice $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }
}


