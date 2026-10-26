<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sync\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Sync\Application\Commands\PushOfflineEvents\PushOfflineEventsCommand;
use StoreYar\Modules\Sync\Application\Commands\RegisterDevice\RegisterDeviceCommand;
use StoreYar\Modules\Sync\Infrastructure\Persistence\Eloquent\SyncDeviceModel;
use StoreYar\Modules\Sync\Infrastructure\Persistence\Eloquent\SyncEventModel;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class SyncController
{
    public function __construct(
        private CommandBus $commands,
        private BusinessContext $business,
    ) {}

    public function registerDevice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_key' => ['required', 'string', 'max:64'],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'platform' => ['sometimes', 'nullable', 'string', 'max:32'],
            'app_version' => ['sometimes', 'nullable', 'string', 'max:32'],
        ]);

        $device = $this->commands->dispatch(
            new RegisterDeviceCommand(
                organizationId: $this->business->businessId(),
                deviceKey: $data['device_key'],
                name: $data['name'] ?? null,
                platform: $data['platform'] ?? null,
                appVersion: $data['app_version'] ?? null,
            ),
        );

        return response()->json([
            'data' => [
                'id' => $device->id,
                'device_key' => $device->device_key,
                'name' => $device->name,
                'platform' => $device->platform,
                'status' => $device->status,
                'last_seen_at' => $device->last_seen_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function push(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['sometimes', 'nullable', 'string', 'size:26'],
            'events' => ['required', 'array', 'min:1', 'max:100'],
            'events.*.client_event_id' => ['required', 'string', 'max:64'],
            'events.*.event_type' => ['required', 'string', 'max:64'],
            'events.*.payload' => ['required', 'array'],
            'events.*.client_occurred_at' => ['sometimes', 'nullable', 'date'],
        ]);

        if (! empty($data['device_id'])) {
            $ok = SyncDeviceModel::query()
                ->where('id', $data['device_id'])
                ->where('organization_id', $this->business->businessId())
                ->exists();
            if (! $ok) {
                return response()->json(['message' => 'Unknown device_id'], 422);
            }
            SyncDeviceModel::query()->where('id', $data['device_id'])->update(['last_seen_at' => now()]);
        }

        $result = $this->commands->dispatch(
            new PushOfflineEventsCommand(
                organizationId: $this->business->businessId(),
                deviceId: $data['device_id'] ?? null,
                events: $data['events'],
            ),
        );

        return response()->json(['data' => $result]);
    }

    public function pullCatalog(Request $request): JsonResponse
    {
        $org = $this->business->businessId();
        $since = $request->query('since'); // ISO datetime optional

        $q = DB::table('catalog_products')
            ->where('organization_id', $org)
            ->orderBy('updated_at')
            ->limit(500);

        if (is_string($since) && $since !== '') {
            $q->where('updated_at', '>', $since);
        }

        $products = $q->get([
            'id', 'sku', 'name', 'status', 'unit_price_amount', 'currency',
            'is_sellable', 'is_stockable', 'updated_at',
        ]);

        $barcodes = DB::table('catalog_product_barcodes')
            ->where('organization_id', $org)
            ->when(is_string($since) && $since !== '', fn ($q) => $q->where('updated_at', '>', $since))
            ->limit(1000)
            ->get(['id', 'product_id', 'barcode', 'type', 'is_primary', 'updated_at']);

        return response()->json([
            'data' => [
                'products' => $products,
                'barcodes' => $barcodes,
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }

    public function pullStock(Request $request): JsonResponse
    {
        $org = $this->business->businessId();
        $warehouseId = $request->query('warehouse_id');

        $q = DB::table('inventory_stock_items')
            ->where('organization_id', $org)
            ->orderBy('updated_at')
            ->limit(1000);

        if (is_string($warehouseId) && $warehouseId !== '') {
            $q->where('warehouse_id', $warehouseId);
        }

        $rows = $q->get([
            'id', 'warehouse_id', 'product_id', 'quantity', 'reserved_quantity', 'version', 'updated_at',
        ]);

        return response()->json([
            'data' => [
                'stock_items' => $rows,
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }

    public function events(Request $request): JsonResponse
    {
        $q = SyncEventModel::query()
            ->where('organization_id', $this->business->businessId())
            ->orderByDesc('created_at')
            ->limit(50);

        if ($request->query('status')) {
            $q->where('status', $request->query('status'));
        }

        return response()->json([
            'data' => $q->get()->map(static fn (SyncEventModel $e) => [
                'id' => $e->id,
                'client_event_id' => $e->client_event_id,
                'event_type' => $e->event_type,
                'status' => $e->status,
                'result_ref' => $e->result_ref,
                'error_message' => $e->error_message,
                'applied_at' => $e->applied_at?->toIso8601String(),
            ]),
        ]);
    }
}
