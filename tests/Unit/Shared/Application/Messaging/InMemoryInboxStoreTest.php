<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Messaging;

use DateTimeImmutable;
use StoreYar\Shared\Application\Contracts\Messaging\InboxMessage;
use StoreYar\Shared\Application\Messaging\InMemoryInboxStore;
use Tests\TestCase;

final class InMemoryInboxStoreTest extends TestCase
{
    public function test_unknown_event_is_not_processed(): void
    {
        $store = new InMemoryInboxStore();

        $this->assertFalse(
            $store->hasProcessed('01EVENT')
        );
    }

    public function test_processed_event_is_detected_by_event_id(): void
    {
        $store = new InMemoryInboxStore();

        $message = new InboxMessage(
            id: '01INBOX',
            eventId: '01EVENT',
            version: 1,
            eventType: 'sales.sale.completed',
            businessId: '01BUSINESS',
            operationId: '01OPERATION',
            correlationId: '01CORRELATION',
            causationId: null,
            occurredAt: new DateTimeImmutable('now'),
        );

        $store->markProcessed($message);

        $this->assertTrue(
            $store->hasProcessed('01EVENT')
        );
    }

    public function test_it_does_not_create_duplicate_event_entries(): void
    {
        $store = new InMemoryInboxStore();

        $first = new InboxMessage(
            id: '01INBOX-FIRST',
            eventId: '01EVENT',
            version: 1,
            eventType: 'sales.sale.completed',
            businessId: '01BUSINESS',
            operationId: '01OPERATION-FIRST',
            correlationId: '01CORRELATION',
            causationId: null,
            occurredAt: new DateTimeImmutable('now'),
        );

        $second = new InboxMessage(
            id: '01INBOX-SECOND',
            eventId: '01EVENT',
            version: 1,
            eventType: 'sales.sale.completed',
            businessId: '01BUSINESS',
            operationId: '01OPERATION-SECOND',
            correlationId: '01CORRELATION',
            causationId: null,
            occurredAt: new DateTimeImmutable('now'),
        );

        $store->markProcessed($first);
        $store->markProcessed($second);

        $this->assertCount(1, $store->messages());
        $this->assertSame(
            $second,
            $store->messages()[0]
        );
    }
}
