<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Commands\CreateCustomer;

use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\CustomerModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;

final class CreateCustomerHandler implements CommandHandler
{
    public function handle(Command $command): CustomerModel
    {
        if (! $command instanceof CreateCustomerCommand) {
            throw new \InvalidArgumentException('Invalid command for CreateCustomerHandler.');
        }

        $code = trim($command->code);
        $name = trim($command->name);
        if ($code === '' || $name === '') {
            throw new \InvalidArgumentException('Customer code and name are required.');
        }

        $exists = CustomerModel::query()
            ->where('organization_id', $command->organizationId)
            ->where('code', $code)
            ->exists();

        if ($exists) {
            throw new \DomainException("Customer code already exists: {$code}");
        }

        $ids = new UlidGenerator();
        $customer = new CustomerModel();
        $customer->id = $ids->generate();
        $customer->organization_id = $command->organizationId;
        $customer->code = $code;
        $customer->name = $name;
        $customer->phone = $command->phone;
        $customer->email = $command->email;
        $customer->customer_type = $command->customerType;
        $customer->credit_limit_amount = $command->creditLimitAmount;
        $customer->currency = strtoupper($command->currency);
        $customer->payment_terms_days = $command->paymentTermsDays;
        $customer->price_level = 'BASE';
        $customer->is_active = true;
        $customer->address = $command->address;
        $customer->notes = $command->notes;
        $customer->outstanding_balance_amount = 0;
        $customer->save();

        return $customer;
    }
}
