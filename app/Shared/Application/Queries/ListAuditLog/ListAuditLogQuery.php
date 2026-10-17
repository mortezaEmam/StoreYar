<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Queries\ListAuditLog;

use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Pagination\PageRequest;

final readonly class ListAuditLogQuery implements Query
{
    public function __construct(
        public string $organizationId,
        public PageRequest $page,
        public ?string $resourceType = null,
        public ?string $resourceId = null,
    ) {
    }
}
