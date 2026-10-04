<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Domain\Events;

use DateTimeImmutable;
use StoreYar\Shared\Domain\Events\DomainEvent;
use Symfony\Component\Uid\Ulid;

final readonly class OrganizationCreated implements DomainEvent
{
    private string $eventId;

    public function __construct(
        private string $organizationId,
        private string $name,
        private DateTimeImmutable $occurredAt,
    ) {
        $this->eventId = (string) new Ulid();
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function aggregateId(): string
    {
        return $this->organizationId;
    }

    public function aggregateType(): string
    {
        return 'organization.organization';
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function name(): string
    {
        return $this->name;
    }
}
