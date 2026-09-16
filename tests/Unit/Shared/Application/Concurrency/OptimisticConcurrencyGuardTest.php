<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Concurrency;

use PHPUnit\Framework\TestCase;
use StoreYar\Shared\Application\Concurrency\OptimisticConcurrencyGuard;

final class OptimisticConcurrencyGuardTest extends TestCase
{
    public function test_matching_versions_are_successful(): void
    {
        $guard = new OptimisticConcurrencyGuard();

        $result = $guard->check(
            expectedVersion: 3,
            actualVersion: 3,
        );

        $this->assertTrue($result->isSuccess());
        $this->assertSame([], $result->data());
        $this->assertNull($result->errorCode());
        $this->assertNull($result->errorMessage());
    }

    public function test_different_versions_create_concurrency_failure(): void
    {
        $guard = new OptimisticConcurrencyGuard();

        $result = $guard->check(
            expectedVersion: 3,
            actualVersion: 4,
        );

        $this->assertTrue($result->isFailure());

        $this->assertSame(
            'concurrency.version_conflict',
            $result->errorCode(),
        );

        $this->assertSame(
            'The resource has been modified by another operation.',
            $result->errorMessage(),
        );

        $this->assertSame(
            [
                'expected_version' => 3,
                'actual_version' => 4,
            ],
            $result->data(),
        );
    }

    public function test_zero_version_is_valid(): void
    {
        $guard = new OptimisticConcurrencyGuard();

        $result = $guard->check(
            expectedVersion: 0,
            actualVersion: 0,
        );

        $this->assertTrue($result->isSuccess());
    }

    public function test_negative_expected_version_is_rejected(): void
    {
        $guard = new OptimisticConcurrencyGuard();

        $result = $guard->check(
            expectedVersion: -1,
            actualVersion: 0,
        );

        $this->assertTrue($result->isFailure());

        $this->assertSame(
            'concurrency.invalid_expected_version',
            $result->errorCode(),
        );
    }

    public function test_negative_actual_version_is_rejected(): void
    {
        $guard = new OptimisticConcurrencyGuard();

        $result = $guard->check(
            expectedVersion: 0,
            actualVersion: -1,
        );

        $this->assertTrue($result->isFailure());

        $this->assertSame(
            'concurrency.invalid_actual_version',
            $result->errorCode(),
        );
    }
}
