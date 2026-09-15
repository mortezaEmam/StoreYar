<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Contracts\Idempotency;

use DateTimeImmutable;

final readonly class IdempotencyRecord
{
    /**
     * @param array<string, mixed> $response
     */
    public function __construct(
        private string $id,
        private string $businessId,
        private string $operationId,
        private string $operationType,
        private string $status,
        private array $response = [],
        private ?DateTimeImmutable $completedAt = null,
    ) {
        if ($this->id === '') {
            throw new \InvalidArgumentException(
                'Idempotency record ID cannot be empty.'
            );
        }

        if ($this->businessId === '') {
            throw new \InvalidArgumentException(
                'Business ID cannot be empty.'
            );
        }

        if ($this->operationId === '') {
            throw new \InvalidArgumentException(
                'Operation ID cannot be empty.'
            );
        }

        if ($this->operationType === '') {
            throw new \InvalidArgumentException(
                'Operation type cannot be empty.'
            );
        }

        if ($this->status === '') {
            throw new \InvalidArgumentException(
                'Idempotency status cannot be empty.'
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

    public function operationId(): string
    {
        return $this->operationId;
    }

    public function operationType(): string
    {
        return $this->operationType;
    }

    public function status(): string
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function response(): array
    {
        return $this->response;
    }

    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }
}
