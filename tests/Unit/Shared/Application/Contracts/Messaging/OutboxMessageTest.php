<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Contracts\Messaging;

use DateTimeImmutable;
use StoreYar\Shared\Application\Contracts\Messaging\OutboxMessage;
use Tests\TestCase;

final class OutboxMessageTest extends TestCase
{
    public function test_it_exposes_message_data(): void
    {
        $occurredAt = new DateTimeImmutable('now');

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
            occurredAt: $occurredAt,
            payload: [
                'total' => '100.00',
            ],
        );

        $this->assertSame('01OUTBOX', $message->id());
        $this->assertSame('01EVENT', $message->eventId());
        $this->assertSame(1, $message->version());
        $this->assertSame('sales.sale.completed', $message->eventType());
        $this->assertSame('01BUSINESS', $message->businessId());
        $this->assertSame('01SALE', $message->aggregateId());
        $this->assertSame('sale', $message->aggregateType());
        $this->assertSame('01OPERATION', $message->operationId());
        $this->assertSame('01CORRELATION', $message->correlationId());
        $this->assertNull($message->causationId());
        $this->assertSame($occurredAt, $message->occurredAt());
        $this->assertSame(['total' => '100.00'], $message->payload());
    }

    public function test_it_rejects_invalid_required_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new OutboxMessage(
            id: '',
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
    }
}
