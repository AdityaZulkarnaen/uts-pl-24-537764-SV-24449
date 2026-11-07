<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class HealthController extends Controller
{
    /**
     * Check system health (database and cache).
     */
    public function check(): JsonResponse
    {
        $checks = [];
        $healthy = true;

        // Check Database
        $dbStart = microtime(true);
        try {
            DB::select('select 1');
            $checks['database'] = [
                'status' => 'up',
                'latency_ms' => (int) round((microtime(true) - $dbStart) * 1000),
            ];
        } catch (Throwable $e) {
            $healthy = false;
            $checks['database'] = [
                'status' => 'down',
                'error' => 'database unreachable',
            ];

            Log::error('health.check_failed', [
                'component' => 'database',
                'reason' => get_class($e),
            ]);
        }

        // Check Cache
        $cacheStart = microtime(true);
        try {
            $key = 'health_check_' . Str::random(8);
            $value = 'ok_' . Str::random(8);

            Cache::put($key, $value, 10);
            $retrieved = Cache::get($key);
            Cache::forget($key);

            if ($retrieved !== $value) {
                throw new \RuntimeException('Cache value mismatch');
            }

            $checks['cache'] = [
                'status' => 'up',
                'latency_ms' => (int) round((microtime(true) - $cacheStart) * 1000),
            ];
        } catch (Throwable $e) {
            $healthy = false;
            $checks['cache'] = [
                'status' => 'down',
                'error' => 'cache unavailable',
            ];

            Log::error('health.check_failed', [
                'component' => 'cache',
                'reason' => get_class($e),
            ]);
        }

        $statusCode = $healthy ? 200 : 503;
        $responsePayload = [
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
        ];

        return response()->json($responsePayload, $statusCode);
    }
}
