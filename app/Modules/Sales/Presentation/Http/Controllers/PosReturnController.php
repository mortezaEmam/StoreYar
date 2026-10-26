<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Sales\Application\Commands\ReturnPosSale\ReturnPosSaleCommand;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class PosReturnController
{
    public function __construct(
        private CommandBus $commands,
        private BusinessContext $business,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pos_sale_id' => ['required', 'string', 'size:26'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'string', 'size:26'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.pos_sale_line_id' => ['sometimes', 'nullable', 'string', 'size:26'],
        ]);

        $result = $this->commands->dispatch(
            new ReturnPosSaleCommand(
                organizationId: $this->business->businessId(),
                posSaleId: $data['pos_sale_id'],
                lines: $data['lines'],
                reason: $data['reason'] ?? null,
            ),
        );

        $ret = $result['return'];

        return response()->json([
            'data' => [
                'id' => $ret->id,
                'document_number' => $ret->document_number,
                'pos_sale_id' => $ret->pos_sale_id,
                'warehouse_id' => $ret->warehouse_id,
                'total_amount' => $ret->total_amount,
                'currency' => $ret->currency,
                'status' => $ret->status,
                'returned_at' => $ret->returned_at?->toIso8601String(),
                'lines' => $result['lines'],
            ],
        ], 201);
    }
}
