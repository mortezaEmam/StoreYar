<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Contracts\Messaging;

use DateTimeImmutable;
use StoreYar\Shared\Application\Contracts\Messaging\InboxMessage;
use Tests\TestCase;

final class InboxMessageTest extends TestCase
{
    public function test_it_exposes_message_data(): void
    {
        $occurredAt = new DateTimeImmutable('now');

        $message = new InboxMessage(
            id: '01INBOX',
            eventId: '01EVENT',
            version: 1,
            eventType: 'sales.sale.completed',
            businessId: '01BUSINESS',
            operationId: '01OPERATION',
            correlationId: '01CORRELATION',
            causationId: null,
            occurredAt: $occurredAt,
            payload: [
                'sale_id' => '01SALE',
            ],
        );

        $this->assertSame('01INBOX', $message->id());
        $this->assertSame('01EVENT', $message->eventId());
        $this->assertSame(1, $message->version());
        $this->assertSame('sales.sale.completed', $message->eventType());
        $this->assertSame('01BUSINESS', $message->businessId());
        $this->assertSame('01OPERATION', $message->operationId());
        $this->assertSame('01CORRELATION', $message->correlationId());
        $this->assertNull($message->causationId());
        $this->assertSame($occurredAt, $message->occurredAt());
        $this->assertSame(['sale_id' => '01SALE'], $message->payload());
    }

    public function test_it_rejects_invalid_required_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new InboxMessage(
            id: '',
            eventId: '01EVENT',
            version: 1,
            eventType: 'sales.sale.completed',
            businessId: '01BUSINESS',
            operationId: '01OPERATION',
            correlationId: '01CORRELATION',
            causationId: null,
            occurredAt: new DateTimeImmutable('now'),
        );
    }
}
