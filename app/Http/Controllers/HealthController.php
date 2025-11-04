<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class HealthController extends Controller
{
    public function check()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $healthy = collect($checks)->every(fn($check) => $check['status'] === 'ok');

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $responseTime = $this->measureTime(fn() => DB::select('SELECT 1'));
            
            return [
                'status' => 'ok',
                'response_time_ms' => $responseTime,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'fail',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkCache(): array
    {
        try {
            $key = 'health_check_' . now()->timestamp;
            Cache::put($key, 'ok', 60);
            $value = Cache::get($key);
            Cache::forget($key);
            
            return [
                'status' => ($value === 'ok') ? 'ok' : 'fail',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'fail',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkStorage(): array
    {
        try {
            $testFile = 'health_check_' . now()->timestamp . '.txt';
            Storage::disk('public')->put($testFile, 'ok');
            $exists = Storage::disk('public')->exists($testFile);
            Storage::disk('public')->delete($testFile);
            
            return [
                'status' => $exists ? 'ok' : 'fail',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'fail',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkQueue(): array
    {
        try {
            // Check if queue connection is available
            $queued = app('queue')->connection();
            return [
                'status' => 'ok',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'fail',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function measureTime(callable $callback): float
    {
        $start = microtime(true);
        $callback();
        return round((microtime(true) - $start) * 1000, 2);
    }
}









