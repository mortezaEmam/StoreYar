<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Application\Queries\GetOrganizationById;

use StoreYar\Shared\Application\Bus\Query\Query;

final readonly class GetOrganizationByIdQuery implements Query
{
    public function __construct(
        public string $organizationId,
    ) {}
}
