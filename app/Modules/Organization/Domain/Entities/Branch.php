<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Domain\Entities;

use DateTimeImmutable;
use StoreYar\Modules\Organization\Domain\Enums\BranchStatus;
use StoreYar\Modules\Organization\Domain\ValueObjects\BranchId;
use StoreYar\Modules\Organization\Domain\ValueObjects\OrganizationId;

final class Branch
{
    private function __construct(
        private readonly BranchId $branchId,
        private readonly OrganizationId $organizationId,
        private string $name,
        private BranchStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private int $version = 0,
    ) {
    }

    public static function create(
        BranchId $id,
        OrganizationId $organizationId,
        string $name,
        DateTimeImmutable $now,
    ): self {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('Branch name is required.');
        }

        return new self(
            branchId: $id,
            organizationId: $organizationId,
            name: $name,
            status: BranchStatus::ACTIVE,
            createdAt: $now,
            updatedAt: $now,
            version: 0,
        );
    }

    public static function reconstitute(
        BranchId $id,
        OrganizationId $organizationId,
        string $name,
        BranchStatus $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        int $version,
    ): self {
        return new self(
            branchId: $id,
            organizationId: $organizationId,
            name: $name,
            status: $status,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            version: $version,
        );
    }

    public function rename(string $name, DateTimeImmutable $now): void
    {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('Branch name is required.');
        }

        $this->name = $name;
        $this->updatedAt = $now;
        $this->version++;
    }

    public function suspend(DateTimeImmutable $now): void
    {
        if (! $this->status->isActive()) {
            throw new \LogicException('Only an active branch can be suspended.');
        }

        $this->status = BranchStatus::SUSPENDED;
        $this->updatedAt = $now;
        $this->version++;
    }

    public function activate(DateTimeImmutable $now): void
    {
        if ($this->status !== BranchStatus::SUSPENDED) {
            throw new \LogicException('Only a suspended branch can be activated.');
        }

        $this->status = BranchStatus::ACTIVE;
        $this->updatedAt = $now;
        $this->version++;
    }

    public function branchId(): BranchId
    {
        return $this->branchId;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function status(): BranchStatus
    {
        return $this->status;
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
