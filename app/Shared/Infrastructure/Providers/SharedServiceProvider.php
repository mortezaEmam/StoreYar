<?php

declare(strict_types=1);

namespace StoreYar\Shared\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use StoreYar\Shared\Application\Contracts\Audit\AuditRecorder;
use StoreYar\Shared\Application\Audit\DefaultAuditRecorder;
use StoreYar\Shared\Application\Context\BusinessContext;
use StoreYar\Shared\Application\Context\CurrentBusinessContext;
use StoreYar\Shared\Application\Contracts\Messaging\InboxStore;
use StoreYar\Shared\Application\Contracts\Messaging\OutboxStore;
use StoreYar\Shared\Domain\Contracts\LockManager;
use StoreYar\Shared\Infrastructure\Concurrency\LaravelLockManager;
use StoreYar\Shared\Infrastructure\Messaging\DatabaseInboxStore;
use StoreYar\Shared\Infrastructure\Messaging\DatabaseOutboxStore;
use StoreYar\Shared\Application\Contracts\Idempotency\IdempotencyStore;
use StoreYar\Shared\Infrastructure\Idempotency\DatabaseIdempotencyStore;

final class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(
            BusinessContext::class,
            CurrentBusinessContext::class,
        );

        $this->app->scoped(
            AuditRecorder::class,
            DefaultAuditRecorder::class,
        );

        $this->app->scoped(
            OutboxStore::class,
            DatabaseOutboxStore::class,
        );

        $this->app->scoped(
            InboxStore::class,
            DatabaseInboxStore::class,
        );

        $this->app->scoped(
            IdempotencyStore::class,
            DatabaseIdempotencyStore::class,
        );

        $this->app->scoped(
            LockManager::class,
            function ($app): LaravelLockManager {
                return new LaravelLockManager(
                    $app->make('cache.store')->getStore(),
                );
            },
        );
    }
}
