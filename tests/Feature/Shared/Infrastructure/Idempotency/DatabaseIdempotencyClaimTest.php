<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Infrastructure\Idempotency;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyRecord;
use StoreYar\Shared\Infrastructure\Id\UlidGenerator;
use StoreYar\Shared\Infrastructure\Idempotency\DatabaseIdempotencyStore;
use Tests\TestCase;

final class DatabaseIdempotencyClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_one_claim_succeeds_for_same_business_and_operation(): void
    {
        $idGenerator = new UlidGenerator();

        $businessId = $idGenerator->generate();
        $operationId = $idGenerator->generate();

        $first = new IdempotencyRecord(
            id: $idGenerator->generate(),
            businessId: $businessId,
            operationId: $operationId,
            operationType: 'sales.create',
            status: 'processing',
        );

        $second = new IdempotencyRecord(
            id: $idGenerator->generate(),
            businessId: $businessId,
            operationId: $operationId,
            operationType: 'sales.create',
            status: 'processing',
        );

        $store = new DatabaseIdempotencyStore(
            app('db')->connection(),
        );

        $this->assertTrue(
            $store->claim($first),
        );

        $this->assertFalse(
            $store->claim($second),
        );

        $stored = $store->find(
            $businessId,
            $operationId,
        );

        $this->assertNotNull($stored);

        $this->assertSame(
            $first->id(),
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

    public function test_claims_for_different_businesses_are_independent(): void
    {
        $idGenerator = new UlidGenerator();

        $operationId = $idGenerator->generate();

        $first = new IdempotencyRecord(
            id: $idGenerator->generate(),
            businessId: $idGenerator->generate(),
            operationId: $operationId,
            operationType: 'sales.create',
            status: 'processing',
        );

        $second = new IdempotencyRecord(
            id: $idGenerator->generate(),
            businessId: $idGenerator->generate(),
            operationId: $operationId,
            operationType: 'sales.create',
            status: 'processing',
        );

        $store = new DatabaseIdempotencyStore(
            app('db')->connection(),
        );

        $this->assertTrue(
            $store->claim($first),
        );

        $this->assertTrue(
            $store->claim($second),
        );

        $this->assertDatabaseCount(
            'integration_idempotency_keys',
            2,
        );
    }
}
