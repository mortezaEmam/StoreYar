<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Idempotency;

use PHPUnit\Framework\TestCase;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyRecord;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyStore;
use StoreYar\Shared\Application\Idempotency\IdempotencyExecutor;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;

final class IdempotencyExecutorFailureTest extends TestCase
{
    public function test_failed_operation_does_not_store_completed_result(): void
    {
        $store = new InMemoryIdempotencyStoreForTest();

        $executor = new IdempotencyExecutor(
            $store,
            new UlidGenerator(),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Simulated operation failure.');

        $executor->execute(
            businessId: 'business-1',
            operationId: 'operation-1',
            operationType: 'sales.create',
            operation: static function (): array {
                throw new \RuntimeException(
                    'Simulated operation failure.',
                );
            },
        );
    }

    public function test_processing_operation_is_not_executed_again(): void
    {
        $store = new InMemoryIdempotencyStoreForTest();

        $record = new IdempotencyRecord(
            id: 'record-1',
            businessId: 'business-1',
            operationId: 'operation-1',
            operationType: 'sales.create',
            status: 'processing',
        );

        self::assertTrue(
            $store->claim($record),
        );

        $executor = new IdempotencyExecutor(
            $store,
            new UlidGenerator(),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Idempotency operation is already being processed.',
        );

        $executor->execute(
            businessId: 'business-1',
            operationId: 'operation-1',
            operationType: 'sales.create',
            operation: static function (): array {
                self::fail(
                    'The operation must not execute again.',
                );
            },
        );
    }
}

final class InMemoryIdempotencyStoreForTest implements IdempotencyStore
{
    /** @var array<string, IdempotencyRecord> */
    private array $records = [];

    public function find(
        string $businessId,
        string $operationId,
    ): ?IdempotencyRecord {
        return $this->records[$this->key(
            $businessId,
            $operationId,
        )] ?? null;
    }

    public function claim(IdempotencyRecord $record): bool
    {
        $key = $this->key(
            $record->businessId(),
            $record->operationId(),
        );

        if (isset($this->records[$key])) {
            return false;
        }

        $this->records[$key] = $record;

        return true;
    }

    public function store(IdempotencyRecord $record): void
    {
        $this->records[$this->key(
            $record->businessId(),
            $record->operationId(),
        )] = $record;
    }

    private function key(
        string $businessId,
        string $operationId,
    ): string {
        return $businessId . ':' . $operationId;
    }
}
