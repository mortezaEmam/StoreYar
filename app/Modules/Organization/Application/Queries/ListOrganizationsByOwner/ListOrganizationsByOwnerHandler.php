<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Application\Queries\ListOrganizationsByOwner;

use StoreYar\Modules\Organization\Domain\Contracts\OrganizationRepository;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;

final class ListOrganizationsByOwnerHandler implements QueryHandler
{
    public function __construct(
        private OrganizationRepository $organizations,
    ) {}

    public function handle(Query $query): array
    {
        if (! $query instanceof ListOrganizationsByOwnerQuery) {
            throw new \InvalidArgumentException(
                'ListOrganizationsByOwnerHandler received an invalid query.',
            );
        }

        return $this->organizations->findByOwnerUserId($query->ownerUserId);
    }
}
