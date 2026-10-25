<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Commands\CreateWarehouse;

use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\WarehouseModel;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\IdGenerator;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;

final class CreateWarehouseHandler implements CommandHandler
{
    public function __construct(
        private readonly ?IdGenerator $ids = null,
    ) {
    }

    public function handle(Command $command): WarehouseModel
    {
        if (! $command instanceof CreateWarehouseCommand) {
            throw new \InvalidArgumentException('Invalid command for CreateWarehouseHandler.');
        }

        $code = strtoupper(trim($command->code));
        if ($code === '' || trim($command->name) === '') {
            throw new \InvalidArgumentException('Warehouse code and name are required.');
        }

        $exists = WarehouseModel::query()
            ->where('organization_id', $command->organizationId)
            ->where('code', $code)
            ->exists();

        if ($exists) {
            throw new \DomainException("Warehouse code already exists: {$code}");
        }

        $ids = $this->ids ?? new UlidGenerator();

        $warehouse = new WarehouseModel();
        $warehouse->id = $ids->generate();
        $warehouse->organization_id = $command->organizationId;
        $warehouse->branch_id = $command->branchId;
        $warehouse->code = $code;
        $warehouse->name = trim($command->name);
        $warehouse->type = $command->type;
        $warehouse->is_active = true;
        $warehouse->address = $command->address;
        $warehouse->save();

        return $warehouse;
    }
}
