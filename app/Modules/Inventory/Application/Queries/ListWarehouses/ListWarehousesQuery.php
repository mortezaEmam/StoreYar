<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Queries\ListWarehouses;

use StoreYar\Shared\Application\Bus\Query\Query;

final readonly class ListWarehousesQuery implements Query
{
    public function __construct(
        public string $organizationId,
    ) {}
}
