<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Application\Queries\ListMembersByOrganization;

use StoreYar\Shared\Application\Bus\Query\Query;

final readonly class ListMembersByOrganizationQuery implements Query
{
    public function __construct(
        public string $organizationId,
    ) {}
}
