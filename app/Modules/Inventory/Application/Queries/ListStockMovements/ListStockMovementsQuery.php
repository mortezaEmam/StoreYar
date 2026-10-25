<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Queries\ListStockMovements;

use StoreYar\Shared\Application\Bus\Query\Query;

final readonly class ListStockMovementsQuery implements Query
{
    public function __construct(
        public string $organizationId,
        public ?string $warehouseId = null,
        public ?string $productId = null,
        public int $limit = 50,
    ) {}
}
