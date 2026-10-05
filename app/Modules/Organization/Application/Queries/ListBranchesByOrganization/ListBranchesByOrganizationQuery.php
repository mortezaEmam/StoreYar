<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Application\Queries\ListBranchesByOrganization;

use StoreYar\Shared\Application\Bus\Query\Query;

final readonly class ListBranchesByOrganizationQuery implements Query
{
    public function __construct(
        public string $organizationId,
        public string $actorUserId,
    ) {}
}
