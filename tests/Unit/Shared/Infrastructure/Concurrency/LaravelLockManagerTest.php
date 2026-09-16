<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Infrastructure\Concurrency;

use Illuminate\Cache\ArrayStore;
use PHPUnit\Framework\TestCase;
use StoreYar\Shared\Infrastructure\Concurrency\LaravelLockManager;

final class LaravelLockManagerTest extends TestCase
{
    public function test_callback_is_executed_when_lock_is_acquired(): void
    {
        $manager = new LaravelLockManager(
            new ArrayStore(),
        );

        $executed = false;

        $result = $manager->acquire(
            key: 'test-lock',
            ttlSeconds: 10,
            callback: static function () use (&$executed): string {
                $executed = true;

                return 'done';
            },
        );

        $this->assertTrue($executed);
        $this->assertSame('done', $result);
    }

    public function test_empty_key_is_rejected(): void
    {
        $manager = new LaravelLockManager(
            new ArrayStore(),
        );

        $this->expectException(\InvalidArgumentException::class);

        $manager->acquire(
            key: '',
            ttlSeconds: 10,
            callback: static fn (): string => 'done',
        );
    }

    public function test_non_positive_ttl_is_rejected(): void
    {
        $manager = new LaravelLockManager(
            new ArrayStore(),
        );

        $this->expectException(\InvalidArgumentException::class);

        $manager->acquire(
            key: 'test-lock',
            ttlSeconds: 0,
            callback: static fn (): string => 'done',
        );
    }


    public function test_callback_is_not_executed_when_lock_cannot_be_acquired(): void
    {
        $store = new ArrayStore();

        $manager = new LaravelLockManager(
            $store,
        );

        $existingLock = $store->lock(
            name: 'test-lock',
            seconds: 10,
        );

        $this->assertTrue(
            $existingLock->get(),
        );

        $executed = false;

        try {
            $manager->acquire(
                key: 'test-lock',
                ttlSeconds: 10,
                callback: static function () use (&$executed): string {
                    $executed = true;

                    return 'should-not-run';
                },
            );
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Unable to acquire lock.',
                $exception->getMessage(),
            );
        } finally {
            $existingLock->release();
        }

        $this->assertFalse($executed);
    }
}
