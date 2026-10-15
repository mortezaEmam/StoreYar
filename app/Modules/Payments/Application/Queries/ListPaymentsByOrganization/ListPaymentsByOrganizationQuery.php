<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Application\Queries\ListPaymentsByOrganization;

use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Pagination\PageRequest;

final readonly class ListPaymentsByOrganizationQuery implements Query
{
    public function __construct(
        public string $organizationId,
        public PageRequest $page,
    ) {
    }
}
