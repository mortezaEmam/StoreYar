<?php

declare(strict_types=1);

namespace StoreYar\Shared\Infrastructure\Outbox;

use Illuminate\Support\Facades\Log;
use StoreYar\Shared\Application\Outbox\OutboxEventPublisher;

final class LogOutboxEventPublisher implements OutboxEventPublisher
{
    public function publish(
        string $eventType,
        string $aggregateType,
        string $aggregateId,
        array $payload,
    ): void {
        Log::info('outbox.published', [
            'event_type' => $eventType,
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'payload' => $payload,
        ]);
    }
}
