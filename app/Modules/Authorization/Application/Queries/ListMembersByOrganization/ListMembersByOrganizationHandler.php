<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Application\Queries\ListMembersByOrganization;

use StoreYar\Modules\Authorization\Domain\Contracts\MembershipRepository;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;

final class ListMembersByOrganizationHandler implements QueryHandler
{
    public function __construct(
        private MembershipRepository $memberships,
    ) {}

    /**
     * @return list<\StoreYar\Modules\Authorization\Domain\Entities\Membership>
     */
    public function handle(Query $query): array
    {
        if (! $query instanceof ListMembersByOrganizationQuery) {
            throw new \InvalidArgumentException(
                'ListMembersByOrganizationHandler received an invalid query.',
            );
        }

        return $this->memberships->findByOrganizationId($query->organizationId);
    }
}
