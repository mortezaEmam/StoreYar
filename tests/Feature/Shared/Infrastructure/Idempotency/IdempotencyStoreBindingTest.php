<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Infrastructure\Idempotency;

use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyStore;
use StoreYar\Shared\Infrastructure\Idempotency\DatabaseIdempotencyStore;
use Tests\TestCase;

final class IdempotencyStoreBindingTest extends TestCase
{
    public function test_it_resolves_database_idempotency_store(): void
    {
        $store = app(IdempotencyStore::class);

        $this->assertInstanceOf(
            DatabaseIdempotencyStore::class,
            $store,
        );
    }

    public function test_it_is_scoped(): void
    {
        $first = app(IdempotencyStore::class);
        $second = app(IdempotencyStore::class);

        $this->assertSame($first, $second);
    }
}
