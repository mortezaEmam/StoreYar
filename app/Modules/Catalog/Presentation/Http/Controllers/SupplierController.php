<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Catalog\Application\Commands\CreateSupplier\CreateSupplierCommand;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\SupplierModel;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class SupplierController
{
    public function __construct(
        private CommandBus $commands,
        private BusinessContext $business,
    ) {}

    public function index(): JsonResponse
    {
        $items = SupplierModel::query()
            ->where('organization_id', $this->business->businessId())
            ->orderBy('code')
            ->get();

        return response()->json([
            'data' => $items->map(fn (SupplierModel $s) => [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->name,
                'phone' => $s->phone,
                'email' => $s->email,
                'tax_id' => $s->tax_id,
                'is_active' => (bool) $s->is_active,
                'outstanding_balance_amount' => $s->outstanding_balance_amount,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'email' => ['sometimes', 'nullable', 'email'],
            'tax_id' => ['sometimes', 'nullable', 'string', 'max:64'],
            'economic_code' => ['sometimes', 'nullable', 'string', 'max:64'],
            'credit_limit_amount' => ['sometimes', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'payment_terms_days' => ['sometimes', 'integer', 'min:0'],
            'address' => ['sometimes', 'nullable', 'string'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        $s = $this->commands->dispatch(
            new CreateSupplierCommand(
                organizationId: $this->business->businessId(),
                code: $data['code'],
                name: $data['name'],
                phone: $data['phone'] ?? null,
                email: $data['email'] ?? null,
                taxId: $data['tax_id'] ?? null,
                economicCode: $data['economic_code'] ?? null,
                creditLimitAmount: (int) ($data['credit_limit_amount'] ?? 0),
                currency: $data['currency'] ?? 'IRR',
                paymentTermsDays: (int) ($data['payment_terms_days'] ?? 0),
                address: $data['address'] ?? null,
                notes: $data['notes'] ?? null,
            ),
        );

        return response()->json([
            'data' => [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->name,
                'phone' => $s->phone,
                'is_active' => (bool) $s->is_active,
            ],
        ], 201);
    }
}
