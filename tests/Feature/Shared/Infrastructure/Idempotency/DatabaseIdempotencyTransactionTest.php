<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Infrastructure\Idempotency;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyRecord;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use StoreYar\Shared\Infrastructure\Idempotency\DatabaseIdempotencyStore;
use Tests\TestCase;

final class DatabaseIdempotencyTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_claim_is_rolled_back_with_transaction(): void
    {
        $idGenerator = new UlidGenerator();

        $businessId = $idGenerator->generate();
        $operationId = $idGenerator->generate();

        $record = new IdempotencyRecord(
            id: $idGenerator->generate(),
            businessId: $businessId,
            operationId: $operationId,
            operationType: 'sales.create',
            status: 'processing',
        );

        $store = new DatabaseIdempotencyStore(
            app('db')->connection(),
        );

        try {
            app('db')->transaction(function () use ($store, $record): void {
                $this->assertTrue(
                    $store->claim($record),
                );

                throw new \RuntimeException(
                    'Simulated business failure.',
                );
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Simulated business failure.',
                $exception->getMessage(),
            );
        }

        $this->assertNull(
            $store->find(
                $businessId,
                $operationId,
            ),
        );

        $this->assertDatabaseCount(
            'integration_idempotency_keys',
            0,
        );
    }

    public function test_claim_is_persisted_when_transaction_commits(): void
    {
        $idGenerator = new UlidGenerator();

        $businessId = $idGenerator->generate();
        $operationId = $idGenerator->generate();

        $record = new IdempotencyRecord(
            id: $idGenerator->generate(),
            businessId: $businessId,
            operationId: $operationId,
            operationType: 'sales.create',
            status: 'processing',
        );

        $store = new DatabaseIdempotencyStore(
            app('db')->connection(),
        );

        app('db')->transaction(function () use ($store, $record): void {
            $this->assertTrue(
                $store->claim($record),
            );
        });

        $stored = $store->find(
            $businessId,
            $operationId,
        );

        $this->assertNotNull($stored);

        $this->assertSame(
            $record->id(),
            $stored->id(),
        );

        $this->assertSame(
            'processing',
            $stored->status(),
        );

        $this->assertDatabaseCount(
            'integration_idempotency_keys',
            1,
        );
    }
}
