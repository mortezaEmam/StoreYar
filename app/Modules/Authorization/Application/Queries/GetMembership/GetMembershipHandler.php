<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Application\Queries\GetMembership;

use StoreYar\Modules\Authorization\Domain\Contracts\MembershipRepository;
use StoreYar\Modules\Authorization\Domain\Entities\Membership;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;

final class GetMembershipHandler implements QueryHandler
{
    public function __construct(
        private MembershipRepository $memberships,
    ) {}

    public function handle(Query $query): ?Membership
    {
        if (! $query instanceof GetMembershipQuery) {
            throw new \InvalidArgumentException(
                'GetMembershipHandler received an invalid query.',
            );
        }

        return $this->memberships->findByOrganizationAndUser(
            $query->organizationId,
            $query->userId,
        );
    }
}
