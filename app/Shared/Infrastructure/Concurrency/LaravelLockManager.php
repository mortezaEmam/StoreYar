<?php

declare(strict_types=1);

namespace StoreYar\Shared\Infrastructure\Concurrency;

use Illuminate\Contracts\Cache\LockProvider;
use RuntimeException;
use StoreYar\Shared\Domain\Contracts\LockManager;

final readonly class LaravelLockManager implements LockManager
{
    public function __construct(
        private LockProvider $lockProvider,
    ) {
    }

    public function acquire(
        string $key,
        int $ttlSeconds,
        callable $callback,
    ): mixed {
        if ($key === '') {
            throw new \InvalidArgumentException(
                'Lock key cannot be empty.',
            );
        }

        if ($ttlSeconds <= 0) {
            throw new \InvalidArgumentException(
                'Lock TTL must be greater than zero.',
            );
        }

        $lock = $this->lockProvider->lock(
            name: $key,
            seconds: $ttlSeconds,
        );

        if (! $lock->get()) {
            throw new RuntimeException(
                'Unable to acquire lock.',
            );
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }
}
