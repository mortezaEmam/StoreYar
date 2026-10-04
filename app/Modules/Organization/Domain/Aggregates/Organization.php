<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Domain\Aggregates;

use DateTimeImmutable;
use StoreYar\Modules\Organization\Domain\Enums\OrganizationStatus;
use StoreYar\Modules\Organization\Domain\Events\OrganizationCreated;
use StoreYar\Modules\Organization\Domain\ValueObjects\OrganizationId;
use StoreYar\Shared\Domain\Aggregates\AggregateRoot;

final class Organization extends AggregateRoot
{
    private OrganizationStatus $status;

    private int $version = 0;

    private function __construct(
        private readonly OrganizationId $organizationId,
        private string $name,
        private readonly string $ownerUserId,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        OrganizationStatus $status,
    ) {
        parent::__construct($organizationId->value());
        $this->status = $status;
    }

    public static function create(
        OrganizationId $id,
        string $name,
        string $ownerUserId,
        DateTimeImmutable $now,
    ): self {
        self::assertRequired($name, 'Organization name');
        self::assertRequired($ownerUserId, 'Organization owner user ID');

        $organization = new self(
            organizationId: $id,
            name: $name,
            ownerUserId: $ownerUserId,
            createdAt: $now,
            updatedAt: $now,
            status: OrganizationStatus::ACTIVE,
        );

        $organization->recordEvent(
            new OrganizationCreated(
                organizationId: $organization->id(),
                name: $organization->name(),
                occurredAt: $now,
            ),
        );

        return $organization;
    }

    public static function reconstitute(
        OrganizationId $id,
        string $name,
        string $ownerUserId,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        OrganizationStatus $status,
        int $version,
    ): self {
        self::assertRequired($name, 'Organization name');
        self::assertRequired($ownerUserId, 'Organization owner user ID');

        if ($version < 0) {
            throw new \InvalidArgumentException(
                'Organization version cannot be negative.',
            );
        }

        $organization = new self(
            organizationId: $id,
            name: $name,
            ownerUserId: $ownerUserId,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            status: $status,
        );

        $organization->version = $version;

        return $organization;
    }

    public function rename(string $name, DateTimeImmutable $now): void
    {
        self::assertRequired($name, 'Organization name');

        $this->name = $name;
        $this->updatedAt = $now;
        $this->version++;
    }

    public function suspend(DateTimeImmutable $now): void
    {
        if ($this->status !== OrganizationStatus::ACTIVE) {
            throw new \LogicException(
                'Only an active organization can be suspended.',
            );
        }

        $this->status = OrganizationStatus::SUSPENDED;
        $this->updatedAt = $now;
        $this->version++;
    }

    public function activate(DateTimeImmutable $now): void
    {
        if ($this->status !== OrganizationStatus::SUSPENDED) {
            throw new \LogicException(
                'Only a suspended organization can be activated.',
            );
        }

        $this->status = OrganizationStatus::ACTIVE;
        $this->updatedAt = $now;
        $this->version++;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function ownerUserId(): string
    {
        return $this->ownerUserId;
    }

    public function status(): OrganizationStatus
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

    private static function assertRequired(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException(
                $field . ' is required.',
            );
        }
    }
}
