<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Infrastructure\Concurrency;

use Illuminate\Contracts\Cache\LockProvider;
use Tests\TestCase;
use StoreYar\Shared\Domain\Contracts\LockManager;
use StoreYar\Shared\Infrastructure\Concurrency\LaravelLockManager;

final class LockManagerBindingTest extends TestCase
{
    public function test_lock_manager_resolves_from_container(): void
    {
        $manager = $this->app->make(LockManager::class);

        $this->assertInstanceOf(
            LaravelLockManager::class,
            $manager,
        );
    }

    public function test_lock_manager_is_scoped(): void
    {
        $first = $this->app->make(LockManager::class);
        $second = $this->app->make(LockManager::class);

        $this->assertSame(
            $first,
            $second,
        );
    }

    public function test_underlying_cache_store_supports_locks(): void
    {
        $store = $this->app
            ->make('cache.store')
            ->getStore();

        $this->assertInstanceOf(
            LockProvider::class,
            $store,
        );
    }
}
