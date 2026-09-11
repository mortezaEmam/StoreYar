<?php

declare(strict_types=1);

namespace StoreYar\Shared\Domain\Events;

interface IntegrationEvent
{
    public function eventId(): string;

    public function version(): int;

    public function occurredAt(): \DateTimeImmutable;

    public function businessId(): string;

    public function aggregateId(): string;

    public function aggregateType(): string;

    public function operationId(): string;

    public function correlationId(): string;

    public function causationId(): ?string;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;
}
