<?php

declare(strict_types=1);

namespace StoreYar\Shared\Domain\Contracts;

interface LockManager
{
    /**
     * @param callable(): mixed $callback
     */
    public function acquire(
        string $key,
        int $ttlSeconds,
        callable $callback
    ): mixed;
}
