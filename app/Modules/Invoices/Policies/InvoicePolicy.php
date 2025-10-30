<?php

namespace App\Modules\Invoices\Policies;

use App\Models\User;
use App\Modules\Invoices\Models\Invoice;

class InvoicePolicy
{
    public function create(User $user): bool
    {
        return $user->can('submit_invoices') || $user->hasRole('Admin');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->can('issue_offers') || $user->hasRole(['Admin','Analyst']);
    }
}


