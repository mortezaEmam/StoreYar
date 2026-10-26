<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sync\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class HealthController
{
    public function __invoke(): JsonResponse
    {
        $checks = [];

        try {
            DB::select('select 1');
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'fail: ' . $e->getMessage();
        }

        $checks['migrations_sample'] = Schema::hasTable('inventory_stock_items') ? 'ok' : 'missing';
        $checks['pos'] = Schema::hasTable('pos_sales') ? 'ok' : 'missing';
        $checks['sync'] = Schema::hasTable('sync_events') ? 'ok' : 'missing';
        $checks['accounting'] = Schema::hasTable('accounting_accounts') ? 'ok' : 'missing';

        $healthy = $checks['database'] === 'ok';

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'checks' => $checks,
            'time' => now()->toIso8601String(),
            'app' => 'StoreYar',
        ], $healthy ? 200 : 503);
    }
}
