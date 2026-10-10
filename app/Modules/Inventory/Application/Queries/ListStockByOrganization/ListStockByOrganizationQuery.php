<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Queries\ListStockByOrganization;

use StoreYar\Shared\Application\Bus\Query\Query;

final readonly class ListStockByOrganizationQuery implements Query
{
    public function __construct(
        public string $organizationId,
    ) {}
}
