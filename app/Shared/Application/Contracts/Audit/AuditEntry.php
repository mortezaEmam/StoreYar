<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Contracts\Audit;

use DateTimeImmutable;

final readonly class AuditEntry
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private string $id,
        private string $businessId,
        private ?string $branchId,
        private ?string $actorId,
        private string $actorType,
        private string $action,
        private string $resourceType,
        private ?string $resourceId,
        private string $operationId,
        private string $correlationId,
        private DateTimeImmutable $occurredAt,
        private array $metadata = [],
    ) {
        if ($this->id === '') {
            throw new \InvalidArgumentException(
                'Audit ID cannot be empty.'
            );
        }

        if ($this->businessId === '') {
            throw new \InvalidArgumentException(
                'Business ID cannot be empty.'
            );
        }

        if ($this->actorType === '') {
            throw new \InvalidArgumentException(
                'Actor type cannot be empty.'
            );
        }

        if ($this->action === '') {
            throw new \InvalidArgumentException(
                'Audit action cannot be empty.'
            );
        }

        if ($this->resourceType === '') {
            throw new \InvalidArgumentException(
                'Audit resource type cannot be empty.'
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

    public function businessId(): string
    {
        return $this->businessId;
    }

    public function branchId(): ?string
    {
        return $this->branchId;
    }

    public function actorId(): ?string
    {
        return $this->actorId;
    }

    public function actorType(): string
    {
        return $this->actorType;
    }

    public function action(): string
    {
        return $this->action;
    }

    public function resourceType(): string
    {
        return $this->resourceType;
    }

    public function resourceId(): ?string
    {
        return $this->resourceId;
    }

    public function operationId(): string
    {
        return $this->operationId;
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }
}
