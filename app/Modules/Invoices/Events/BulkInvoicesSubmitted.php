<?php

namespace App\Modules\Invoices\Events;

use App\Modules\Invoices\Models\Invoice;
use App\Models\Supplier;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BulkInvoicesSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $invoices,
        public Supplier $supplier
    ) {}
}

