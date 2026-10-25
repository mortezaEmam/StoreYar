<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Commands\CreateUnit;

use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\UnitModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;

final class CreateUnitHandler implements CommandHandler
{
    public function handle(Command $command): UnitModel
    {
        if (! $command instanceof CreateUnitCommand) {
            throw new \InvalidArgumentException('Invalid command for CreateUnitHandler.');
        }

        $code = strtoupper(trim($command->code));
        if ($code === '' || trim($command->name) === '') {
            throw new \InvalidArgumentException('Unit code and name are required.');
        }

        if (UnitModel::query()->where('organization_id', $command->organizationId)->where('code', $code)->exists()) {
            throw new \DomainException("Unit code already exists: {$code}");
        }

        $unit = new UnitModel();
        $unit->id = (new UlidGenerator())->generate();
        $unit->organization_id = $command->organizationId;
        $unit->code = $code;
        $unit->name = trim($command->name);
        $unit->symbol = $command->symbol;
        $unit->is_base = $command->isBase;
        $unit->save();

        return $unit;
    }
}
