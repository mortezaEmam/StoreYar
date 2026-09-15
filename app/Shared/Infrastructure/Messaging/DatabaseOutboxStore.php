<?php

declare(strict_types=1);

namespace StoreYar\Shared\Infrastructure\Messaging;

use Illuminate\Database\ConnectionInterface;
use StoreYar\Shared\Application\Contracts\Messaging\OutboxMessage;
use StoreYar\Shared\Application\Contracts\Messaging\OutboxStore;

final readonly class DatabaseOutboxStore implements OutboxStore
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {
    }

    public function store(OutboxMessage $message): void
    {
        $this->connection
            ->table('integration_outbox_messages')
            ->insert([
                'id' => $message->id(),
                'event_id' => $message->eventId(),
                'version' => $message->version(),
                'event_type' => $message->eventType(),
                'business_id' => $message->businessId(),
                'aggregate_id' => $message->aggregateId(),
                'aggregate_type' => $message->aggregateType(),
                'operation_id' => $message->operationId(),
                'correlation_id' => $message->correlationId(),
                'causation_id' => $message->causationId(),
                'occurred_at' => $message->occurredAt(),
                'payload' => json_encode(
                    $message->payload(),
                    JSON_THROW_ON_ERROR,
                ),
                'published_at' => null,
                'attempts' => 0,
                'available_at' => null,
                'last_attempted_at' => null,
                'last_error' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
