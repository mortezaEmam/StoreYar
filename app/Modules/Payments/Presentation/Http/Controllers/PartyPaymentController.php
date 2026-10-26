<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Payments\Application\Commands\RecordCustomerPayment\RecordCustomerPaymentCommand;
use StoreYar\Modules\Payments\Application\Commands\RecordSupplierPayment\RecordSupplierPaymentCommand;
use StoreYar\Modules\Payments\Infrastructure\Persistence\Eloquent\PartyPaymentModel;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class PartyPaymentController
{
    public function __construct(
        private CommandBus $commands,
        private BusinessContext $business,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $q = PartyPaymentModel::query()
            ->where('organization_id', $this->business->businessId())
            ->orderByDesc('paid_at')
            ->limit(50);

        if ($request->query('party_type')) {
            $q->where('party_type', strtoupper((string) $request->query('party_type')));
        }
        if ($request->query('party_id')) {
            $q->where('party_id', $request->query('party_id'));
        }

        return response()->json([
            'data' => $q->get()->map(static fn (PartyPaymentModel $p) => [
                'id' => $p->id,
                'party_type' => $p->party_type,
                'party_id' => $p->party_id,
                'direction' => $p->direction,
                'amount' => $p->amount,
                'currency' => $p->currency,
                'method' => $p->method,
                'document_number' => $p->document_number,
                'status' => $p->status,
                'journal_entry_id' => $p->journal_entry_id,
                'paid_at' => $p->paid_at?->toIso8601String(),
            ]),
        ]);
    }

    public function customer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'string', 'size:26'],
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['sometimes', 'string', 'in:CASH,BANK,CARD'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'reference' => ['sometimes', 'nullable', 'string', 'max:128'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        $p = $this->commands->dispatch(
            new RecordCustomerPaymentCommand(
                organizationId: $this->business->businessId(),
                customerId: $data['customer_id'],
                amount: (int) $data['amount'],
                method: $data['method'] ?? 'CASH',
                currency: $data['currency'] ?? 'IRR',
                reference: $data['reference'] ?? null,
                notes: $data['notes'] ?? null,
            ),
        );

        return response()->json(['data' => $this->toArray($p)], 201);
    }

    public function supplier(Request $request): JsonResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'string', 'size:26'],
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['sometimes', 'string', 'in:CASH,BANK,CARD'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'reference' => ['sometimes', 'nullable', 'string', 'max:128'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        $p = $this->commands->dispatch(
            new RecordSupplierPaymentCommand(
                organizationId: $this->business->businessId(),
                supplierId: $data['supplier_id'],
                amount: (int) $data['amount'],
                method: $data['method'] ?? 'CASH',
                currency: $data['currency'] ?? 'IRR',
                reference: $data['reference'] ?? null,
                notes: $data['notes'] ?? null,
            ),
        );

        return response()->json(['data' => $this->toArray($p)], 201);
    }

    private function toArray(PartyPaymentModel $p): array
    {
        return [
            'id' => $p->id,
            'party_type' => $p->party_type,
            'party_id' => $p->party_id,
            'direction' => $p->direction,
            'amount' => $p->amount,
            'currency' => $p->currency,
            'method' => $p->method,
            'document_number' => $p->document_number,
            'status' => $p->status,
            'journal_entry_id' => $p->journal_entry_id,
            'paid_at' => $p->paid_at?->toIso8601String(),
        ];
    }
}
