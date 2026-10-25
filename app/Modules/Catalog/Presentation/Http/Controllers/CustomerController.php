<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use StoreYar\Modules\Catalog\Application\Commands\CreateCustomer\CreateCustomerCommand;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\CustomerModel;
use StoreYar\Modules\Catalog\Presentation\Http\Requests\CreateCustomerRequest;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class CustomerController
{
    public function __construct(
        private CommandBus $commands,
        private BusinessContext $business,
    ) {}

    public function index(): JsonResponse
    {
        $items = CustomerModel::query()
            ->where('organization_id', $this->business->businessId())
            ->orderBy('code')
            ->get()
            ->map(fn (CustomerModel $c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
                'phone' => $c->phone,
                'email' => $c->email,
                'customer_type' => $c->customer_type,
                'credit_limit_amount' => $c->credit_limit_amount,
                'currency' => $c->currency,
                'payment_terms_days' => $c->payment_terms_days,
                'is_active' => (bool) $c->is_active,
                'outstanding_balance_amount' => $c->outstanding_balance_amount,
            ]);

        return response()->json(['data' => $items]);
    }

    public function store(CreateCustomerRequest $request): JsonResponse
    {
        $customer = $this->commands->dispatch(
            new CreateCustomerCommand(
                organizationId: $this->business->businessId(),
                code: $request->string('code')->toString(),
                name: $request->string('name')->toString(),
                phone: $request->input('phone'),
                email: $request->input('email'),
                customerType: $request->input('customer_type', 'individual'),
                creditLimitAmount: (int) $request->input('credit_limit_amount', 0),
                currency: $request->input('currency', 'IRR'),
                paymentTermsDays: (int) $request->input('payment_terms_days', 0),
                address: $request->input('address'),
                notes: $request->input('notes'),
            ),
        );

        return response()->json([
            'data' => [
                'id' => $customer->id,
                'code' => $customer->code,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'customer_type' => $customer->customer_type,
                'credit_limit_amount' => $customer->credit_limit_amount,
                'currency' => $customer->currency,
                'is_active' => (bool) $customer->is_active,
            ],
        ], 201);
    }
}
