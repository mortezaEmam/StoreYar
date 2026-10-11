<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Outbox;

interface OutboxEventPublisher
{
    /**
     * @param array<string, mixed> $payload
     */
    public function publish(
        string $eventType,
        string $aggregateType,
        string $aggregateId,
        array $payload,
    ): void;
}
