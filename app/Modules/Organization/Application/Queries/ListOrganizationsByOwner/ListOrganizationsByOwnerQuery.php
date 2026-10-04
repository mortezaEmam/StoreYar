<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Application\Queries\ListOrganizationsByOwner;

use StoreYar\Shared\Application\Bus\Query\Query;

final readonly class ListOrganizationsByOwnerQuery implements Query
{
    public function __construct(
        public string $ownerUserId,
    ) {}
}
