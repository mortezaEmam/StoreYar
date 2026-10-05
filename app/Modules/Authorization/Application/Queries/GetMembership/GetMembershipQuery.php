<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Application\Queries\GetMembership;

use StoreYar\Shared\Application\Bus\Query\Query;

final readonly class GetMembershipQuery implements Query
{
    public function __construct(
        public string $organizationId,
        public string $userId,
    ) {}
}
