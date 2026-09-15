<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Infrastructure\Idempotency;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyRecord;
use StoreYar\Shared\Infrastructure\Idempotency\DatabaseIdempotencyStore;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use Tests\TestCase;

final class DatabaseIdempotencyStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_and_finds_record(): void
    {
        $idGenerator = new UlidGenerator();

        $record = new IdempotencyRecord(
            id: $idGenerator->generate(),
            businessId: $idGenerator->generate(),
            operationId: $idGenerator->generate(),
            operationType: 'sales.create',
            status: 'completed',
            response: [
                'sale_id' => $idGenerator->generate(),
            ],
            completedAt: new \DateTimeImmutable(
                '2026-01-01 10:00:00+00:00',
            ),
        );

        $store = new DatabaseIdempotencyStore(
            app('db')->connection(),
        );

        $store->store($record);

        $found = $store->find(
            $record->businessId(),
            $record->operationId(),
        );

        $this->assertNotNull($found);

        $this->assertSame(
            $record->id(),
            $found->id(),
        );

        $this->assertSame(
            $record->businessId(),
            $found->businessId(),
        );

        $this->assertSame(
            $record->operationId(),
            $found->operationId(),
        );

        $this->assertSame(
            $record->operationType(),
            $found->operationType(),
        );

        $this->assertSame(
            $record->status(),
            $found->status(),
        );

        $this->assertSame(
            $record->response(),
            $found->response(),
        );

        $this->assertNotNull($found->completedAt());

        $this->assertDatabaseHas(
            'integration_idempotency_keys',
            [
                'id' => $record->id(),
                'business_id' => $record->businessId(),
                'operation_id' => $record->operationId(),
            ],
        );
    }

    public function test_unknown_operation_returns_null(): void
    {
        $store = new DatabaseIdempotencyStore(
            app('db')->connection(),
        );

        $idGenerator = new UlidGenerator();

        $this->assertNull(
            $store->find(
                $idGenerator->generate(),
                $idGenerator->generate(),
            ),
        );
    }

    public function test_same_operation_id_is_isolated_by_business(): void
    {
        $idGenerator = new UlidGenerator();

        $operationId = $idGenerator->generate();

        $first = new IdempotencyRecord(
            id: $idGenerator->generate(),
            businessId: $idGenerator->generate(),
            operationId: $operationId,
            operationType: 'sales.create',
            status: 'completed',
            response: ['sale_id' => 'sale-1'],
        );

        $second = new IdempotencyRecord(
            id: $idGenerator->generate(),
            businessId: $idGenerator->generate(),
            operationId: $operationId,
            operationType: 'sales.create',
            status: 'completed',
            response: ['sale_id' => 'sale-2'],
        );

        $store = new DatabaseIdempotencyStore(
            app('db')->connection(),
        );

        $store->store($first);
        $store->store($second);

        $this->assertSame(
            ['sale_id' => 'sale-1'],
            $store->find(
                $first->businessId(),
                $operationId,
            )?->response(),
        );

        $this->assertSame(
            ['sale_id' => 'sale-2'],
            $store->find(
                $second->businessId(),
                $operationId,
            )?->response(),
        );
    }

    public function test_duplicate_business_and_operation_is_rejected(): void
    {
        $idGenerator = new UlidGenerator();

        $businessId = $idGenerator->generate();
        $operationId = $idGenerator->generate();

        $first = new IdempotencyRecord(
            id: $idGenerator->generate(),
            businessId: $businessId,
            operationId: $operationId,
            operationType: 'sales.create',
            status: 'completed',
        );

        $second = new IdempotencyRecord(
            id: $idGenerator->generate(),
            businessId: $businessId,
            operationId: $operationId,
            operationType: 'sales.create',
            status: 'completed',
        );

        $store = new DatabaseIdempotencyStore(
            app('db')->connection(),
        );

        $store->store($first);

        $this->expectException(\Throwable::class);

        $store->store($second);
    }
}
