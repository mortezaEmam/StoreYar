<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Inventory\Application\Commands\CreateStockCount\CreateStockCountCommand;
use StoreYar\Modules\Inventory\Application\Commands\PostStockCount\PostStockCountCommand;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\StockCountModel;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class StockCountController
{
    public function __construct(
        private CommandBus $commands,
        private BusinessContext $business,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $q = StockCountModel::query()
            ->where('organization_id', $this->business->businessId())
            ->orderByDesc('created_at')
            ->limit(50);

        if ($request->query('status')) {
            $q->where('status', $request->query('status'));
        }
        if ($request->query('warehouse_id')) {
            $q->where('warehouse_id', $request->query('warehouse_id'));
        }

        return response()->json([
            'data' => $q->get()->map(fn (StockCountModel $c) => $this->summary($c)),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $c = StockCountModel::query()
            ->where('id', $id)
            ->where('organization_id', $this->business->businessId())
            ->with('lines')
            ->first();

        if ($c === null) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(['data' => $this->detail($c)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'string', 'size:26'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'string', 'size:26'],
            'lines.*.counted_quantity' => ['required', 'integer', 'min:0'],
        ]);

        $c = $this->commands->dispatch(
            new CreateStockCountCommand(
                organizationId: $this->business->businessId(),
                warehouseId: $data['warehouse_id'],
                lines: $data['lines'],
                notes: $data['notes'] ?? null,
            ),
        );

        return response()->json(['data' => $this->detail($c)], 201);
    }

    public function post(string $id): JsonResponse
    {
        $c = $this->commands->dispatch(
            new PostStockCountCommand(
                organizationId: $this->business->businessId(),
                stockCountId: $id,
            ),
        );

        return response()->json(['data' => $this->detail($c)]);
    }

    private function summary(StockCountModel $c): array
    {
        return [
            'id' => $c->id,
            'document_number' => $c->document_number,
            'warehouse_id' => $c->warehouse_id,
            'status' => $c->status,
            'counted_at' => $c->counted_at?->toIso8601String(),
            'posted_at' => $c->posted_at?->toIso8601String(),
        ];
    }

    private function detail(StockCountModel $c): array
    {
        $c->loadMissing('lines');

        return [
            'id' => $c->id,
            'document_number' => $c->document_number,
            'warehouse_id' => $c->warehouse_id,
            'status' => $c->status,
            'notes' => $c->notes,
            'counted_at' => $c->counted_at?->toIso8601String(),
            'posted_at' => $c->posted_at?->toIso8601String(),
            'lines' => $c->lines->map(static fn ($l) => [
                'id' => $l->id,
                'product_id' => $l->product_id,
                'system_quantity' => $l->system_quantity,
                'counted_quantity' => $l->counted_quantity,
                'variance_quantity' => $l->variance_quantity,
                'adjustment_movement_id' => $l->adjustment_movement_id,
            ])->values()->all(),
        ];
    }
}
