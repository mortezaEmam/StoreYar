<?php

declare(strict_types=1);

namespace StoreYar\Shared\Infrastructure\Outbox;

use StoreYar\Shared\Application\Outbox\OutboxEventPublisher;

final class QueueOutboxEventPublisher implements OutboxEventPublisher
{
    public function publish(
        string $eventType,
        string $aggregateType,
        string $aggregateId,
        array $payload,
    ): void {
        PublishOutboxEventJob::dispatch(
            $eventType,
            $aggregateType,
            $aggregateId,
            $payload,
        );
    }
}
