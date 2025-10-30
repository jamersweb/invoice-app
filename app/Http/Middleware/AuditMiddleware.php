<?php

namespace App\Http\Middleware;

use App\Models\AuditEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuditMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generate or retrieve correlation ID
        $correlationId = $request->header('X-Correlation-ID')
            ?? $request->header('X-Request-ID')
            ?? Str::uuid()->toString();

        // Set in response header for tracing
        $request->headers->set('X-Correlation-ID', $correlationId);

        /** @var Response $response */
        $response = $next($request);

        // Set correlation ID in response
        $response->headers->set('X-Correlation-ID', $correlationId);

        try {
            if (auth()->check()) {
                $route = $request->route();
                $actionName = is_string($route?->getActionName()) ? $route->getActionName() : 'unknown';

                AuditEvent::create([
                    'actor_type' => \App\Models\User::class,
                    'actor_id' => auth()->id(),
                    'entity_type' => 'http',
                    'entity_id' => 0,
                    'action' => $request->method() . ' ' . ($route?->getName() ?? $request->path()),
                    'diff_json' => $this->extractPayload($request),
                    'ip' => $this->maskIp($request->ip()),
                    'ua' => (string) $request->header('User-Agent'),
                    'correlation_id' => $correlationId,
                ]);
            }
        } catch (\Throwable $e) {
            // Avoid blocking requests due to audit failures
            \Log::error('Audit middleware error', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
        }

        return $response;
    }

    private function maskIp(?string $ip): ?string
    {
        if (!$ip) return null;
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.***';
        }
        return $ip;
    }

    private function extractPayload(Request $request): array
    {
        $input = $request->except(['password', 'password_confirmation', '_token']);
        return [
            'input' => $input,
        ];
    }
}
