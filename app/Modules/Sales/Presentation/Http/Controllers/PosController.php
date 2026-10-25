<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Sales\Application\Commands\ClosePosSession\ClosePosSessionCommand;
use StoreYar\Modules\Sales\Application\Commands\OpenPosSession\OpenPosSessionCommand;
use StoreYar\Modules\Sales\Application\Commands\PosQuickSale\PosQuickSaleCommand;
use StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent\PosSaleModel;
use StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent\PosSessionModel;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class PosController
{
    public function __construct(
        private CommandBus $commands,
        private BusinessContext $business,
    ) {}

    public function openSession(Request $request): JsonResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'string', 'size:26'],
            'register_code' => ['sometimes', 'string', 'max:32'],
            'opening_float_amount' => ['sometimes', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'branch_id' => ['sometimes', 'nullable', 'string', 'size:26'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        $s = $this->commands->dispatch(
            new OpenPosSessionCommand(
                organizationId: $this->business->businessId(),
                warehouseId: $data['warehouse_id'],
                registerCode: $data['register_code'] ?? 'POS1',
                openingFloatAmount: (int) ($data['opening_float_amount'] ?? 0),
                currency: $data['currency'] ?? 'IRR',
                branchId: $data['branch_id'] ?? null,
                notes: $data['notes'] ?? null,
            ),
        );

        return response()->json(['data' => $this->sessionArray($s)], 201);
    }

    public function closeSession(string $id, Request $request): JsonResponse
    {
        $data = $request->validate([
            'closing_count_amount' => ['required', 'integer', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        $s = $this->commands->dispatch(
            new ClosePosSessionCommand(
                organizationId: $this->business->businessId(),
                sessionId: $id,
                closingCountAmount: (int) $data['closing_count_amount'],
                notes: $data['notes'] ?? null,
            ),
        );

        return response()->json(['data' => $this->sessionArray($s)]);
    }

    public function sessions(Request $request): JsonResponse
    {
        $q = PosSessionModel::query()
            ->where('organization_id', $this->business->businessId())
            ->orderByDesc('opened_at')
            ->limit(50);

        if ($request->query('status')) {
            $q->where('status', $request->query('status'));
        }

        return response()->json([
            'data' => $q->get()->map(fn ($s) => $this->sessionArray($s)),
        ]);
    }

    public function currentSession(Request $request): JsonResponse
    {
        $register = $request->query('register_code', 'POS1');
        $s = PosSessionModel::query()
            ->where('organization_id', $this->business->businessId())
            ->where('register_code', $register)
            ->where('status', 'OPEN')
            ->first();

        if ($s === null) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => $this->sessionArray($s)]);
    }

    public function sale(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pos_session_id' => ['required', 'string', 'size:26'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['sometimes', 'string', 'size:26'],
            'lines.*.barcode' => ['sometimes', 'string', 'max:64'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['sometimes', 'string', 'in:CASH,CARD,MIXED,CREDIT'],
            'customer_id' => ['sometimes', 'nullable', 'string', 'size:26'],
            'discount_amount' => ['sometimes', 'integer', 'min:0'],
        ]);

        $result = $this->commands->dispatch(
            new PosQuickSaleCommand(
                organizationId: $this->business->businessId(),
                posSessionId: $data['pos_session_id'],
                lines: $data['lines'],
                paymentMethod: $data['payment_method'] ?? 'CASH',
                customerId: $data['customer_id'] ?? null,
                discountAmount: (int) ($data['discount_amount'] ?? 0),
            ),
        );

        /** @var PosSaleModel $sale */
        $sale = $result['sale'];

        return response()->json([
            'data' => [
                'id' => $sale->id,
                'document_number' => $sale->document_number,
                'pos_session_id' => $sale->pos_session_id,
                'warehouse_id' => $sale->warehouse_id,
                'subtotal_amount' => $sale->subtotal_amount,
                'discount_amount' => $sale->discount_amount,
                'total_amount' => $sale->total_amount,
                'currency' => $sale->currency,
                'payment_method' => $sale->payment_method,
                'status' => $sale->status,
                'sold_at' => $sale->sold_at?->toIso8601String(),
                'lines' => $result['lines'],
            ],
        ], 201);
    }

    private function sessionArray(PosSessionModel $s): array
    {
        return [
            'id' => $s->id,
            'warehouse_id' => $s->warehouse_id,
            'register_code' => $s->register_code,
            'status' => $s->status,
            'opened_at' => $s->opened_at?->toIso8601String(),
            'opening_float_amount' => $s->opening_float_amount,
            'closed_at' => $s->closed_at?->toIso8601String(),
            'closing_count_amount' => $s->closing_count_amount,
            'variance_amount' => $s->variance_amount,
            'currency' => $s->currency,
        ];
    }
}
