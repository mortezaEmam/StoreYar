<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Contracts\Messaging;

use DateTimeImmutable;

final readonly class OutboxMessage
{
    public function __construct(
        private string $id,
        private string $eventId,
        private int $version,
        private string $eventType,
        private string $businessId,
        private string $aggregateId,
        private string $aggregateType,
        private string $operationId,
        private string $correlationId,
        private ?string $causationId,
        private DateTimeImmutable $occurredAt,
        private array $payload = [],
    ) {
        if ($this->id === '') {
            throw new \InvalidArgumentException(
                'Outbox message ID cannot be empty.'
            );
        }

        if ($this->eventId === '') {
            throw new \InvalidArgumentException(
                'Event ID cannot be empty.'
            );
        }

        if ($this->version < 1) {
            throw new \InvalidArgumentException(
                'Event version must be greater than zero.'
            );
        }

        if ($this->eventType === '') {
            throw new \InvalidArgumentException(
                'Event type cannot be empty.'
            );
        }

        if ($this->businessId === '') {
            throw new \InvalidArgumentException(
                'Business ID cannot be empty.'
            );
        }

        if ($this->aggregateId === '') {
            throw new \InvalidArgumentException(
                'Aggregate ID cannot be empty.'
            );
        }

        if ($this->aggregateType === '') {
            throw new \InvalidArgumentException(
                'Aggregate type cannot be empty.'
            );
        }

        if ($this->operationId === '') {
            throw new \InvalidArgumentException(
                'Operation ID cannot be empty.'
            );
        }

        if ($this->correlationId === '') {
            throw new \InvalidArgumentException(
                'Correlation ID cannot be empty.'
            );
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function eventType(): string
    {
        return $this->eventType;
    }

    public function businessId(): string
    {
        return $this->businessId;
    }

    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    public function aggregateType(): string
    {
        return $this->aggregateType;
    }

    public function operationId(): string
    {
        return $this->operationId;
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    public function causationId(): ?string
    {
        return $this->causationId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }
}
