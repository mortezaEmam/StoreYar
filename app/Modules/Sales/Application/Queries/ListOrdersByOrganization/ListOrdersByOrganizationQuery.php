<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Queries\ListOrdersByOrganization;

use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Pagination\PageRequest;

final readonly class ListOrdersByOrganizationQuery implements Query
{
    public function __construct(
        public string $organizationId,
        public PageRequest $page,
    ) {}
}
