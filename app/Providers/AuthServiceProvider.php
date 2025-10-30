<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Modules\Invoices\Models\Invoice::class => \App\Modules\Invoices\Policies\InvoicePolicy::class,
        \App\Models\Document::class => \App\Policies\DocumentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}


