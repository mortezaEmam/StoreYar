<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Domain\Entities;

use DateTimeImmutable;
use StoreYar\Modules\Authorization\Domain\Enums\Role;
use StoreYar\Modules\Authorization\Domain\ValueObjects\MembershipId;

final class Membership
{
    private function __construct(
        private readonly MembershipId $membershipId,
        private readonly string $organizationId,
        private readonly string $userId,
        private Role $role,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private int $version = 0,
    ) {
    }

    public static function create(
        MembershipId $id,
        string $organizationId,
        string $userId,
        Role $role,
        DateTimeImmutable $now,
    ): self {
        if (trim($organizationId) === '') {
            throw new \InvalidArgumentException('Organization ID is required.');
        }

        if (trim($userId) === '') {
            throw new \InvalidArgumentException('User ID is required.');
        }

        return new self(
            membershipId: $id,
            organizationId: $organizationId,
            userId: $userId,
            role: $role,
            createdAt: $now,
            updatedAt: $now,
            version: 0,
        );
    }

    public static function reconstitute(
        MembershipId $id,
        string $organizationId,
        string $userId,
        Role $role,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        int $version,
    ): self {
        return new self(
            membershipId: $id,
            organizationId: $organizationId,
            userId: $userId,
            role: $role,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            version: $version,
        );
    }

    public function changeRole(Role $role, DateTimeImmutable $now): void
    {
        $this->role = $role;
        $this->updatedAt = $now;
        $this->version++;
    }

    public function membershipId(): MembershipId
    {
        return $this->membershipId;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function role(): Role
    {
        return $this->role;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function version(): int
    {
        return $this->version;
    }
}
