<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Infrastructure\Messaging;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Shared\Application\Contracts\Messaging\OutboxMessage;
use StoreYar\Shared\Infrastructure\Messaging\DatabaseOutboxStore;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use Tests\TestCase;

final class DatabaseOutboxStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_outbox_message(): void
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

        $this->assertDatabaseHas('integration_outbox_messages', [
            'id' => $message->id(),
            'event_id' => $message->eventId(),
            'business_id' => $message->businessId(),
            'event_type' => $message->eventType(),
        ]);
    }

    public function test_duplicate_event_id_is_rejected(): void
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
            payload: [],
        );

        $store = app(DatabaseOutboxStore::class);

        $store->store($message);

        $this->expectException(\Throwable::class);

        $store->store(
            new OutboxMessage(
                id: $idGenerator->generate(),
                eventId: $message->eventId(),
                version: 1,
                eventType: 'sales.sale.created',
                businessId: $message->businessId(),
                aggregateId: $message->aggregateId(),
                aggregateType: $message->aggregateType,
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
