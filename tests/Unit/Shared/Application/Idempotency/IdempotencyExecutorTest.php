<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Idempotency;

use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyRecord;
use StoreYar\Shared\Application\Idempotency\IdempotencyExecutor;
use StoreYar\Shared\Application\Idempotency\InMemoryIdempotencyStore;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use Tests\TestCase;

final class IdempotencyExecutorTest extends TestCase
{
    public function test_it_executes_new_operation_and_stores_result(): void
    {
        $store = new InMemoryIdempotencyStore();

        $executor = new IdempotencyExecutor(
            store: $store,
            idGenerator: new UlidGenerator(),
        );

        $executions = 0;

        $result = $executor->execute(
            businessId: 'business-1',
            operationId: 'operation-1',
            operationType: 'sales.create',
            operation: function () use (&$executions): array {
                $executions++;

                return [
                    'sale_id' => 'sale-1',
                ];
            },
        );

        $this->assertFalse($result->replayed());

        $this->assertSame(
            ['sale_id' => 'sale-1'],
            $result->response(),
        );

        $this->assertSame(1, $executions);

        $this->assertNotNull(
            $store->find(
                'business-1',
                'operation-1',
            ),
        );
    }

    public function test_duplicate_operation_replays_stored_result_without_reexecution(): void
    {
        $store = new InMemoryIdempotencyStore();

        $store->store(
            new IdempotencyRecord(
                id: '01J12345678901234567890123',
                businessId: 'business-1',
                operationId: 'operation-1',
                operationType: 'sales.create',
                status: 'completed',
                response: [
                    'sale_id' => 'existing-sale',
                ],
            ),
        );

        $executor = new IdempotencyExecutor(
            store: $store,
            idGenerator: new UlidGenerator(),
        );

        $executions = 0;

        $result = $executor->execute(
            businessId: 'business-1',
            operationId: 'operation-1',
            operationType: 'sales.create',
            operation: function () use (&$executions): array {
                $executions++;

                return [
                    'sale_id' => 'new-sale',
                ];
            },
        );

        $this->assertTrue($result->replayed());

        $this->assertSame(
            ['sale_id' => 'existing-sale'],
            $result->response(),
        );

        $this->assertSame(0, $executions);
    }

    public function test_same_operation_id_in_another_business_is_not_replayed(): void
    {
        $store = new InMemoryIdempotencyStore();

        $store->store(
            new IdempotencyRecord(
                id: '01J12345678901234567890123',
                businessId: 'business-1',
                operationId: 'operation-1',
                operationType: 'sales.create',
                status: 'completed',
                response: [
                    'sale_id' => 'sale-1',
                ],
            ),
        );

        $executor = new IdempotencyExecutor(
            store: $store,
            idGenerator: new UlidGenerator(),
        );

        $executions = 0;

        $result = $executor->execute(
            businessId: 'business-2',
            operationId: 'operation-1',
            operationType: 'sales.create',
            operation: function () use (&$executions): array {
                $executions++;

                return [
                    'sale_id' => 'sale-2',
                ];
            },
        );

        $this->assertFalse($result->replayed());

        $this->assertSame(
            ['sale_id' => 'sale-2'],
            $result->response(),
        );

        $this->assertSame(1, $executions);
    }

    public function test_processing_operation_is_not_executed_again(): void
    {
        $store = new InMemoryIdempotencyStore();

        $store->claim(
            new IdempotencyRecord(
                id: '01J12345678901234567890123',
                businessId: 'business-1',
                operationId: 'operation-1',
                operationType: 'sales.create',
                status: 'processing',
            ),
        );

        $executor = new IdempotencyExecutor(
            store: $store,
            idGenerator: new UlidGenerator(),
        );

        $this->expectException(\RuntimeException::class);

        $executor->execute(
            businessId: 'business-1',
            operationId: 'operation-1',
            operationType: 'sales.create',
            operation: static fn (): array => [
                'sale_id' => 'must-not-run',
            ],
        );
    }
}
