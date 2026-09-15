<?php

declare(strict_types=1);

namespace StoreYar\Shared\Infrastructure\Messaging;

use Illuminate\Database\ConnectionInterface;
use StoreYar\Shared\Application\Contracts\Messaging\InboxMessage;
use StoreYar\Shared\Application\Contracts\Messaging\InboxStore;

final readonly class DatabaseInboxStore implements InboxStore
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {
    }

    public function hasProcessed(string $eventId): bool
    {
        return $this->connection
            ->table('integration_inbox_messages')
            ->where('event_id', $eventId)
            ->exists();
    }

    public function markProcessed(InboxMessage $message): void
    {
        $this->connection
            ->table('integration_inbox_messages')
            ->insert([
                'id' => $message->id(),
                'event_id' => $message->eventId(),
                'version' => $message->version(),
                'event_type' => $message->eventType(),
                'business_id' => $message->businessId(),
                'operation_id' => $message->operationId(),
                'correlation_id' => $message->correlationId(),
                'causation_id' => $message->causationId(),
                'occurred_at' => $message->occurredAt(),
                'payload' => json_encode(
                    $message->payload(),
                    JSON_THROW_ON_ERROR,
                ),
                'processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
