<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Commands\CreateWarehouse;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class CreateWarehouseCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $code,
        public string $name,
        public string $type = 'MAIN',
        public ?string $branchId = null,
        public ?string $address = null,
    ) {}
}
