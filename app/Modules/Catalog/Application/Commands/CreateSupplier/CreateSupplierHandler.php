<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Commands\CreateSupplier;

use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\SupplierModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;

final class CreateSupplierHandler implements CommandHandler
{
    public function handle(Command $command): SupplierModel
    {
        if (! $command instanceof CreateSupplierCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }

        $code = trim($command->code);
        $name = trim($command->name);
        if ($code === '' || $name === '') {
            throw new \InvalidArgumentException('Supplier code and name are required.');
        }

        if (SupplierModel::query()->where('organization_id', $command->organizationId)->where('code', $code)->exists()) {
            throw new \DomainException("Supplier code already exists: {$code}");
        }

        $s = new SupplierModel();
        $s->id = (new UlidGenerator())->generate();
        $s->organization_id = $command->organizationId;
        $s->code = $code;
        $s->name = $name;
        $s->phone = $command->phone;
        $s->email = $command->email;
        $s->tax_id = $command->taxId;
        $s->economic_code = $command->economicCode;
        $s->credit_limit_amount = $command->creditLimitAmount;
        $s->currency = strtoupper($command->currency);
        $s->payment_terms_days = $command->paymentTermsDays;
        $s->is_active = true;
        $s->address = $command->address;
        $s->notes = $command->notes;
        $s->outstanding_balance_amount = 0;
        $s->save();

        return $s;
    }
}
