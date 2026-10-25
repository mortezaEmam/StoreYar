<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Inventory\Application\Commands\ReceiveStock\ReceiveStockCommand;
use StoreYar\Modules\Inventory\Application\Queries\ListStockMovements\ListStockMovementsQuery;
use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\StockItemModel;
use StoreYar\Modules\Inventory\Presentation\Http\Requests\ReceiveStockRequest;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Query\QueryBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class StockReceiveController
{
    public function __construct(
        private CommandBus $commands,
        private QueryBus $queries,
        private BusinessContext $business,
    ) {}

    public function receive(ReceiveStockRequest $request): JsonResponse
    {
        $result = $this->commands->dispatch(
            new ReceiveStockCommand(
                organizationId: $this->business->businessId(),
                warehouseId: $request->string('warehouse_id')->toString(),
                productId: $request->string('product_id')->toString(),
                quantity: $request->integer('quantity'),
                reason: $request->input('reason'),
                documentType: 'STOCK_RECEIPT',
                documentId: $request->input('document_id'),
                createdBy: null,
                unitCostAmount: $request->has('unit_cost_amount')
                    ? $request->integer('unit_cost_amount')
                    : null,
                currency: $request->input('currency', 'IRR'),
            ),
        );

        /** @var StockItemModel $item */
        $item = $result['stock_item'];
        $movement = $result['movement'];

        return response()->json([
            'data' => [
                'stock_item' => [
                    'id' => $item->id,
                    'warehouse_id' => $item->warehouse_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'reserved_quantity' => $item->reserved_quantity,
                    'version' => $item->version,
                ],
                'movement' => [
                    'id' => $movement->id,
                    'movement_type' => $movement->movement_type,
                    'direction' => $movement->direction,
                    'quantity_delta' => $movement->quantity_delta,
                    'quantity_after' => $movement->quantity_after,
                    'warehouse_id' => $movement->warehouse_id,
                    'occurred_at' => $movement->occurred_at?->toIso8601String(),
                ],
                'quantity_before' => $result['quantity_before'],
                'quantity_after' => $result['quantity_after'],
            ],
        ], 201);
    }

    public function movements(Request $request): JsonResponse
    {
        $items = $this->queries->ask(
            new ListStockMovementsQuery(
                organizationId: $this->business->businessId(),
                warehouseId: $request->query('warehouse_id'),
                productId: $request->query('product_id'),
                limit: (int) $request->query('limit', 50),
            ),
        );

        $data = array_map(static function ($m) {
            return [
                'id' => $m->id,
                'warehouse_id' => $m->warehouse_id,
                'product_id' => $m->product_id,
                'movement_type' => $m->movement_type,
                'document_type' => $m->document_type,
                'document_id' => $m->document_id,
                'direction' => $m->direction,
                'quantity_delta' => $m->quantity_delta,
                'quantity_after' => $m->quantity_after,
                'reserved_after' => $m->reserved_after,
                'reason' => $m->reason,
                'occurred_at' => $m->occurred_at?->toIso8601String(),
            ];
        }, $items);

        return response()->json(['data' => $data]);
    }

    public function byWarehouse(Request $request): JsonResponse
    {
        $warehouseId = $request->query('warehouse_id');
        if (! is_string($warehouseId) || $warehouseId === '') {
            return response()->json(['message' => 'warehouse_id is required'], 422);
        }

        $items = StockItemModel::query()
            ->where('organization_id', $this->business->businessId())
            ->where('warehouse_id', $warehouseId)
            ->orderBy('product_id')
            ->get();

        return response()->json([
            'data' => $items->map(static fn (StockItemModel $i) => [
                'id' => $i->id,
                'warehouse_id' => $i->warehouse_id,
                'product_id' => $i->product_id,
                'quantity' => $i->quantity,
                'reserved_quantity' => $i->reserved_quantity,
                'available' => (int) $i->quantity,
                'version' => $i->version,
            ]),
        ]);
    }
}
