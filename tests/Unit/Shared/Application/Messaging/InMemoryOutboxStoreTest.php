<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Messaging;

use DateTimeImmutable;
use StoreYar\Shared\Application\Contracts\Messaging\OutboxMessage;
use StoreYar\Shared\Application\Messaging\InMemoryOutboxStore;
use Tests\TestCase;

final class InMemoryOutboxStoreTest extends TestCase
{
    public function test_it_stores_messages(): void
    {
        $store = new InMemoryOutboxStore();

        $message = new OutboxMessage(
            id: '01OUTBOX',
            eventId: '01EVENT',
            version: 1,
            eventType: 'sales.sale.completed',
            businessId: '01BUSINESS',
            aggregateId: '01SALE',
            aggregateType: 'sale',
            operationId: '01OPERATION',
            correlationId: '01CORRELATION',
            causationId: null,
            occurredAt: new DateTimeImmutable('now'),
        );

        $store->store($message);

        $this->assertCount(1, $store->messages());
        $this->assertSame($message, $store->messages()[0]);
    }

    public function test_it_preserves_message_order(): void
    {
        $store = new InMemoryOutboxStore();

        $first = new OutboxMessage(
            id: '01FIRST',
            eventId: '01EVENT-FIRST',
            version: 1,
            eventType: 'sales.sale.created',
            businessId: '01BUSINESS',
            aggregateId: '01SALE-FIRST',
            aggregateType: 'sale',
            operationId: '01OPERATION-FIRST',
            correlationId: '01CORRELATION',
            causationId: null,
            occurredAt: new DateTimeImmutable('now'),
        );

        $second = new OutboxMessage(
            id: '01SECOND',
            eventId: '01EVENT-SECOND',
            version: 1,
            eventType: 'sales.sale.completed',
            businessId: '01BUSINESS',
            aggregateId: '01SALE-SECOND',
            aggregateType: 'sale',
            operationId: '01OPERATION-SECOND',
            correlationId: '01CORRELATION',
            causationId: null,
            occurredAt: new DateTimeImmutable('now'),
        );

        $store->store($first);
        $store->store($second);

        $this->assertSame(
            [$first, $second],
            $store->messages()
        );
    }
}
