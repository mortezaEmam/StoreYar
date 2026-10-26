<?php

declare(strict_types=1);

namespace StoreYar\Modules\Accounting\Application\Commands\CreateAccount;

use StoreYar\Modules\Accounting\Infrastructure\Persistence\Eloquent\AccountModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;

final class CreateAccountHandler implements CommandHandler
{
    private const TYPES = ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE'];

    public function handle(Command $command): AccountModel
    {
        if (! $command instanceof CreateAccountCommand) {
            throw new \InvalidArgumentException('Invalid command.');
        }

        $type = strtoupper(trim($command->type));
        if (! in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException('Invalid account type.');
        }

        $code = trim($command->code);
        $name = trim($command->name);
        if ($code === '' || $name === '') {
            throw new \InvalidArgumentException('Code and name are required.');
        }

        if (AccountModel::query()->where('organization_id', $command->organizationId)->where('code', $code)->exists()) {
            throw new \DomainException("Account code already exists: {$code}");
        }

        $a = new AccountModel();
        $a->id = (new UlidGenerator())->generate();
        $a->organization_id = $command->organizationId;
        $a->code = $code;
        $a->name = $name;
        $a->type = $type;
        $a->subtype = $command->subtype ? strtoupper($command->subtype) : null;
        $a->parent_id = $command->parentId;
        $a->is_system = $command->isSystem;
        $a->is_active = true;
        $a->currency = $command->currency;
        $a->save();

        return $a;
    }
}
