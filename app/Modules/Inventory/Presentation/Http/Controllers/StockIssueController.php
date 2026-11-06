<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Inventory\Application\Commands\IssueStock\IssueStockCommand;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class StockIssueController
{
    public function __construct(
        private CommandBus $commands,
        private BusinessContext $business,
    ) {}

    public function issue(Request $request): JsonResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'string', 'size:26'],
            'product_id' => ['required', 'string', 'size:26'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $result = $this->commands->dispatch(
            new IssueStockCommand(
                organizationId: $this->business->businessId(),
                warehouseId: $data['warehouse_id'],
                productId: $data['product_id'],
                quantity: (int) $data['quantity'],
                reason: $data['reason'] ?? 'Manual issue',
                documentType: 'STOCK_ISSUE',
                documentId: null,
                createdBy: null,
            ),
        );

        $item = $result['stock_item'] ?? $result['item'] ?? null;
        $movement = $result['movement'] ?? null;

        return response()->json([
            'data' => [
                'stock_item' => $item ? [
                    'id' => $item->id,
                    'warehouse_id' => $item->warehouse_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ] : null,
                'movement' => $movement ? [
                    'id' => $movement->id,
                    'quantity_delta' => $movement->quantity_delta ?? null,
                    'quantity_after' => $movement->quantity_after ?? ($result['quantity_after'] ?? null),
                ] : null,
                'quantity_after' => $result['quantity_after'] ?? null,
            ],
        ], 201);
    }
}
