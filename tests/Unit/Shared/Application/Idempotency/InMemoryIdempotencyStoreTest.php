<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Idempotency;

use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyRecord;
use StoreYar\Shared\Application\Idempotency\InMemoryIdempotencyStore;
use Tests\TestCase;

final class InMemoryIdempotencyStoreTest extends TestCase
{
    public function test_unknown_operation_returns_null(): void
    {
        $store = new InMemoryIdempotencyStore();

        $this->assertNull(
            $store->find(
                '01J12345678901234567890123',
                '01J12345678901234567890124',
            ),
        );
    }

    public function test_it_stores_and_finds_record_by_business_and_operation(): void
    {
        $store = new InMemoryIdempotencyStore();

        $record = new IdempotencyRecord(
            id: '01J12345678901234567890125',
            businessId: '01J12345678901234567890123',
            operationId: '01J12345678901234567890124',
            operationType: 'sales.create',
            status: 'completed',
            response: [
                'sale_id' => '01J12345678901234567890126',
            ],
        );

        $store->store($record);

        $this->assertSame(
            $record,
            $store->find(
                $record->businessId(),
                $record->operationId(),
            ),
        );
    }

    public function test_same_operation_id_in_different_businesses_is_isolated(): void
    {
        $store = new InMemoryIdempotencyStore();

        $operationId = '01J12345678901234567890124';

        $first = new IdempotencyRecord(
            id: '01J12345678901234567890125',
            businessId: '01J12345678901234567890123',
            operationId: $operationId,
            operationType: 'sales.create',
            status: 'completed',
            response: ['sale_id' => 'sale-1'],
        );

        $second = new IdempotencyRecord(
            id: '01J12345678901234567890127',
            businessId: '01J12345678901234567890128',
            operationId: $operationId,
            operationType: 'sales.create',
            status: 'completed',
            response: ['sale_id' => 'sale-2'],
        );

        $store->store($first);
        $store->store($second);

        $this->assertSame(
            $first,
            $store->find(
                $first->businessId(),
                $operationId,
            ),
        );

        $this->assertSame(
            $second,
            $store->find(
                $second->businessId(),
                $operationId,
            ),
        );
    }

    public function test_storing_same_business_and_operation_replaces_previous_record(): void
    {
        $store = new InMemoryIdempotencyStore();

        $businessId = '01J12345678901234567890123';
        $operationId = '01J12345678901234567890124';

        $first = new IdempotencyRecord(
            id: '01J12345678901234567890125',
            businessId: $businessId,
            operationId: $operationId,
            operationType: 'sales.create',
            status: 'processing',
        );

        $second = new IdempotencyRecord(
            id: '01J12345678901234567890126',
            businessId: $businessId,
            operationId: $operationId,
            operationType: 'sales.create',
            status: 'completed',
            response: ['sale_id' => 'sale-1'],
        );

        $store->store($first);
        $store->store($second);

        $this->assertSame(
            $second,
            $store->find(
                $businessId,
                $operationId,
            ),
        );
    }


    public function test_claim_succeeds_only_once(): void
    {
        $store = new InMemoryIdempotencyStore();

        $first = new IdempotencyRecord(
            id: '01J12345678901234567890125',
            businessId: '01J12345678901234567890123',
            operationId: '01J12345678901234567890124',
            operationType: 'sales.create',
            status: 'processing',
        );

        $second = new IdempotencyRecord(
            id: '01J12345678901234567890126',
            businessId: $first->businessId(),
            operationId: $first->operationId(),
            operationType: 'sales.create',
            status: 'processing',
        );

        $this->assertTrue($store->claim($first));
        $this->assertFalse($store->claim($second));

        $this->assertSame(
            $first,
            $store->find(
                $first->businessId(),
                $first->operationId(),
            ),
        );
    }
}
