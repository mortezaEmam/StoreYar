<?php

declare(strict_types=1);

namespace StoreYar\Shared\Domain\Events;

interface DomainEvent
{
    public function eventId(): string;

    public function occurredAt(): \DateTimeImmutable;

    public function aggregateId(): string;

    public function aggregateType(): string;
}
