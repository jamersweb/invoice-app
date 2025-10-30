<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use App\Modules\Invoices\Contracts\OcrServiceInterface;
use App\Modules\Invoices\Services\TesseractOcrService;
use App\Services\Agreements\ESignServiceInterface;
use App\Services\Agreements\InternalESignService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OcrServiceInterface::class, function () {
            return new TesseractOcrService();
        });
        $this->app->bind(ESignServiceInterface::class, InternalESignService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        RateLimiter::for('uploads', function ($request) {
            return [Limit::perMinute(10)->by(optional($request->user())->id ?: $request->ip())];
        });
        // Ensure permission cache is reset on boot in non-production to reflect seeder changes
        if (!app()->environment('production')) {
            try { app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions(); } catch (\Throwable $e) {}
        }
    }
}
