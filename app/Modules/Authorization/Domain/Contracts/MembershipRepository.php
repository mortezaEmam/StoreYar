<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Domain\Contracts;

use StoreYar\Modules\Authorization\Domain\Entities\Membership;
use StoreYar\Modules\Authorization\Domain\ValueObjects\MembershipId;

interface MembershipRepository
{
    public function findById(MembershipId $id): ?Membership;

    public function findByOrganizationAndUser(
        string $organizationId,
        string $userId,
    ): ?Membership;

    /**
     * @return list<Membership>
     */
    public function findByOrganizationId(string $organizationId): array;

    /**
     * @return list<Membership>
     */
    public function findByUserId(string $userId): array;

    public function save(Membership $membership): void;


    public function delete(Membership $membership): void;
}
