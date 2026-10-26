<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Inventory\Application\Commands\TransferStock\TransferStockCommand;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class StockTransferController
{
    public function __construct(
        private CommandBus $commands,
        private BusinessContext $business,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from_warehouse_id' => ['required', 'string', 'size:26'],
            'to_warehouse_id' => ['required', 'string', 'size:26', 'different:from_warehouse_id'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'string', 'size:26'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $result = $this->commands->dispatch(
            new TransferStockCommand(
                organizationId: $this->business->businessId(),
                fromWarehouseId: $data['from_warehouse_id'],
                toWarehouseId: $data['to_warehouse_id'],
                lines: $data['lines'],
                notes: $data['notes'] ?? null,
            ),
        );

        return response()->json(['data' => $result], 201);
    }
}
