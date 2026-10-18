<?php

declare(strict_types=1);

namespace StoreYar\Shared\Infrastructure\Messaging;

use Illuminate\Database\ConnectionInterface;
use StoreYar\Shared\Application\Contracts\Messaging\OutboxMessage;
use StoreYar\Shared\Application\Contracts\Messaging\OutboxStore;

/**
 * Canonical outbox storage for messaging OutboxMessage.
 * Writes to shared_outbox (same table as domain EloquentOutbox).
 * integration_outbox_messages is deprecated and unused by application code.
 */
final readonly class DatabaseOutboxStore implements OutboxStore
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {
    }

    public function store(OutboxMessage $message): void
    {
        $occurredAt = $message->occurredAt();
        if ($occurredAt instanceof \DateTimeInterface) {
            $occurredAtFormatted = $occurredAt->format('Y-m-d H:i:s.u');
        } else {
            $occurredAtFormatted = (string) $occurredAt;
        }

        $this->connection
            ->table('shared_outbox')
            ->insert([
                'id' => $message->id(),
                'aggregate_type' => $message->aggregateType(),
                'aggregate_id' => $message->aggregateId(),
                'event_type' => $message->eventType(),
                'payload' => json_encode(
                    [
                        'event_id' => $message->eventId(),
                        'version' => $message->version(),
                        'business_id' => $message->businessId(),
                        'operation_id' => $message->operationId(),
                        'correlation_id' => $message->correlationId(),
                        'causation_id' => $message->causationId(),
                        'body' => $message->payload(),
                    ],
                    JSON_THROW_ON_ERROR,
                ),
                'occurred_at' => $occurredAtFormatted,
                'created_at' => now(),
                'processed_at' => null,
                'attempts' => 0,
                'last_error' => null,
            ]);
    }
}
