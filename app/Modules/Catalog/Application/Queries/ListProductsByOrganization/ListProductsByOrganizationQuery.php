<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Queries\ListProductsByOrganization;

use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Pagination\PageRequest;

final readonly class ListProductsByOrganizationQuery implements Query
{
    public function __construct(
        public string $organizationId,
        public PageRequest $page,
    ) {}
}
