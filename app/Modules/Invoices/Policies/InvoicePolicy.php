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

    public function view(User $user, Invoice $invoice): bool
    {
        return ($invoice->user_id && $user->id == $invoice->user_id) || $user->hasRole(['Admin', 'Analyst']);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return ($invoice->user_id && $user->id == $invoice->user_id) || $user->hasRole(['Admin', 'Analyst']);
    }
}


