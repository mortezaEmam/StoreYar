<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Application\Queries\ListBranchesByOrganization;

use StoreYar\Modules\Organization\Domain\Contracts\BranchRepository;
use StoreYar\Modules\Organization\Domain\Contracts\OrganizationRepository;
use StoreYar\Modules\Organization\Domain\ValueObjects\OrganizationId;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;

final class ListBranchesByOrganizationHandler implements QueryHandler
{
    public function __construct(
        private OrganizationRepository $organizations,
        private BranchRepository $branches,
    ) {}

    /**
     * @return list<\StoreYar\Modules\Organization\Domain\Entities\Branch>
     */
    public function handle(Query $query): array
    {
        if (! $query instanceof ListBranchesByOrganizationQuery) {
            throw new \InvalidArgumentException(
                'ListBranchesByOrganizationHandler received an invalid query.',
            );
        }

        $organization = $this->organizations->findById(
            OrganizationId::fromString($query->organizationId),
        );

        if ($organization === null) {
            throw new \InvalidArgumentException('Organization not found.');
        }

//        if ($organization->ownerUserId() !== $query->actorUserId) {
//            throw new \InvalidArgumentException('Not allowed.');
//        }

        return $this->branches->findByOrganizationId(
            $organization->organizationId(),
        );
    }
}
