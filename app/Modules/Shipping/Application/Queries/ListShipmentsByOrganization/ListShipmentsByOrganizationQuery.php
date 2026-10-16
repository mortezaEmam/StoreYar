<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Application\Queries\ListShipmentsByOrganization;

use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Pagination\PageRequest;

final readonly class ListShipmentsByOrganizationQuery implements Query
{
    public function __construct(
        public string $organizationId,
        public PageRequest $page,
    ) {
    }
}
