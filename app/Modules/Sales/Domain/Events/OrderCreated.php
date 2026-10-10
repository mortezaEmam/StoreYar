<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Domain\Events;

use DateTimeImmutable;
use StoreYar\Shared\Domain\Events\DomainEvent;
use Symfony\Component\Uid\Ulid;

final readonly class OrderCreated implements DomainEvent
{
    private string $eventId;

    public function __construct(
        private string $orderId,
        private string $organizationId,
        private string $customerUserId,
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
        return $this->orderId;
    }

    public function aggregateType(): string
    {
        return 'sales.order';
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function customerUserId(): string
    {
        return $this->customerUserId;
    }
}
