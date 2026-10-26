<?php

declare(strict_types=1);

namespace StoreYar\Modules\Purchasing\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Purchasing\Application\Commands\ReturnPurchase\ReturnPurchaseCommand;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class PurchaseReturnController
{
    public function __construct(
        private CommandBus $commands,
        private BusinessContext $business,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'string', 'size:26'],
            'warehouse_id' => ['required', 'string', 'size:26'],
            'purchase_order_id' => ['sometimes', 'nullable', 'string', 'size:26'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'string', 'size:26'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.unit_cost_amount' => ['sometimes', 'integer', 'min:0'],
        ]);

        $result = $this->commands->dispatch(
            new ReturnPurchaseCommand(
                organizationId: $this->business->businessId(),
                supplierId: $data['supplier_id'],
                warehouseId: $data['warehouse_id'],
                lines: $data['lines'],
                purchaseOrderId: $data['purchase_order_id'] ?? null,
                reason: $data['reason'] ?? null,
            ),
        );

        $ret = $result['return'];

        return response()->json([
            'data' => [
                'id' => $ret->id,
                'document_number' => $ret->document_number,
                'supplier_id' => $ret->supplier_id,
                'warehouse_id' => $ret->warehouse_id,
                'total_amount' => $ret->total_amount,
                'status' => $ret->status,
                'returned_at' => $ret->returned_at?->toIso8601String(),
                'lines' => $result['lines'],
            ],
        ], 201);
    }
}
