<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Infrastructure\Messaging;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Shared\Application\Contracts\Messaging\OutboxMessage;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use StoreYar\Shared\Infrastructure\Messaging\DatabaseOutboxStore;
use Tests\TestCase;

final class DatabaseOutboxStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_outbox_message_in_shared_outbox(): void
    {
        $idGenerator = new UlidGenerator();

        $message = new OutboxMessage(
            id: $idGenerator->generate(),
            eventId: $idGenerator->generate(),
            version: 1,
            eventType: 'sales.sale.created',
            businessId: $idGenerator->generate(),
            aggregateId: $idGenerator->generate(),
            aggregateType: 'sales.sale',
            operationId: $idGenerator->generate(),
            correlationId: $idGenerator->generate(),
            causationId: null,
            occurredAt: new \DateTimeImmutable(
                '2026-01-01 10:00:00+00:00',
            ),
            payload: [
                'sale_id' => $idGenerator->generate(),
            ],
        );

        $store = app(DatabaseOutboxStore::class);

        $store->store($message);

        $this->assertDatabaseHas('shared_outbox', [
            'id' => $message->id(),
            'event_type' => $message->eventType(),
            'aggregate_id' => $message->aggregateId(),
            'aggregate_type' => $message->aggregateType(),
        ]);

        $this->assertDatabaseMissing('integration_outbox_messages', [
            'id' => $message->id(),
        ]);
    }

    public function test_duplicate_id_is_rejected(): void
    {
        $idGenerator = new UlidGenerator();
        $id = $idGenerator->generate();

        $message = new OutboxMessage(
            id: $id,
            eventId: $idGenerator->generate(),
            version: 1,
            eventType: 'sales.sale.created',
            businessId: $idGenerator->generate(),
            aggregateId: $idGenerator->generate(),
            aggregateType: 'sales.sale',
            operationId: $idGenerator->generate(),
            correlationId: $idGenerator->generate(),
            causationId: null,
            occurredAt: new \DateTimeImmutable(
                '2026-01-01 10:00:00+00:00',
            ),
            payload: [],
        );

        $store = app(DatabaseOutboxStore::class);

        $store->store($message);

        $this->expectException(\Throwable::class);

        $store->store(
            new OutboxMessage(
                id: $id,
                eventId: $idGenerator->generate(),
                version: 1,
                eventType: 'sales.sale.created',
                businessId: $message->businessId(),
                aggregateId: $message->aggregateId(),
                aggregateType: $message->aggregateType(),
                operationId: $idGenerator->generate(),
                correlationId: $message->correlationId(),
                causationId: null,
                occurredAt: new \DateTimeImmutable(
                    '2026-01-01 10:01:00+00:00',
                ),
                payload: [],
            ),
        );
    }
}
