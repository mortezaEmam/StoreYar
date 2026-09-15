<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Infrastructure\Messaging;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Shared\Application\Contracts\Messaging\InboxMessage;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use StoreYar\Shared\Infrastructure\Messaging\DatabaseInboxStore;
use Tests\TestCase;

final class DatabaseInboxStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_detects_processed_event(): void
    {
        $idGenerator = new UlidGenerator();

        $message = new InboxMessage(
            id: $idGenerator->generate(),
            eventId: $idGenerator->generate(),
            version: 1,
            eventType: 'sales.sale.created',
            businessId: $idGenerator->generate(),
            operationId: $idGenerator->generate(),
            correlationId: $idGenerator->generate(),
            causationId: null,
            occurredAt: new \DateTimeImmutable(
                '2026-01-01 10:00:00+00:00',
            ),
            payload: [],
        );

        $store = app(DatabaseInboxStore::class);

        $this->assertFalse(
            $store->hasProcessed($message->eventId()),
        );

        $store->markProcessed($message);

        $this->assertTrue(
            $store->hasProcessed($message->eventId()),
        );

        $this->assertDatabaseHas('integration_inbox_messages', [
            'event_id' => $message->eventId(),
            'business_id' => $message->businessId(),
        ]);
    }

    public function test_duplicate_event_id_is_rejected(): void
    {
        $idGenerator = new UlidGenerator();

        $message = new InboxMessage(
            id: $idGenerator->generate(),
            eventId: $idGenerator->generate(),
            version: 1,
            eventType: 'sales.sale.created',
            businessId: $idGenerator->generate(),
            operationId: $idGenerator->generate(),
            correlationId: $idGenerator->generate(),
            causationId: null,
            occurredAt: new \DateTimeImmutable(
                '2026-01-01 10:00:00+00:00',
            ),
            payload: [],
        );

        $store = app(DatabaseInboxStore::class);

        $store->markProcessed($message);

        $this->expectException(\Throwable::class);

        $store->markProcessed(
            new InboxMessage(
                id: $idGenerator->generate(),
                eventId: $message->eventId(),
                version: 1,
                eventType: 'sales.sale.created',
                businessId: $message->businessId(),
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
