<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Generate correlation ID if not present
        $correlationId = $request->header('X-Correlation-ID')
            ?? $request->header('X-Request-ID')
            ?? \Illuminate\Support\Str::uuid()->toString();

        // Store in request for logging
        $request->headers->set('X-Correlation-ID', $correlationId);

        $user = $request->user();

        // Load roles relationship if user exists
        if ($user) {
            $user->load('roles');
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'supplier' => $user && $user->hasRole('Supplier')
                    ? \App\Models\Supplier::where('contact_email', $user->email)->first()
                    : null,
                'ip' => $request->ip(),
            ],
            'ziggy' => fn() => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'correlation_id' => $correlationId,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
            ],
        ];
    }
}
