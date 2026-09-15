<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Contracts\Idempotency;

use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyRecord;
use Tests\TestCase;

final class IdempotencyRecordTest extends TestCase
{
    public function test_it_exposes_record_data(): void
    {
        $completedAt = new \DateTimeImmutable(
            '2026-01-01 10:00:00+00:00',
        );

        $record = new IdempotencyRecord(
            id: '01J12345678901234567890123',
            businessId: '01J12345678901234567890124',
            operationId: '01J12345678901234567890125',
            operationType: 'sales.create',
            status: 'completed',
            response: [
                'sale_id' => '01J12345678901234567890126',
            ],
            completedAt: $completedAt,
        );

        $this->assertSame(
            '01J12345678901234567890123',
            $record->id(),
        );

        $this->assertSame(
            '01J12345678901234567890124',
            $record->businessId(),
        );

        $this->assertSame(
            '01J12345678901234567890125',
            $record->operationId(),
        );

        $this->assertSame(
            'sales.create',
            $record->operationType(),
        );

        $this->assertSame(
            'completed',
            $record->status(),
        );

        $this->assertSame(
            [
                'sale_id' => '01J12345678901234567890126',
            ],
            $record->response(),
        );

        $this->assertSame(
            $completedAt,
            $record->completedAt(),
        );
    }

    public function test_required_values_cannot_be_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new IdempotencyRecord(
            id: '',
            businessId: 'business',
            operationId: 'operation',
            operationType: 'sales.create',
            status: 'completed',
        );
    }
}
