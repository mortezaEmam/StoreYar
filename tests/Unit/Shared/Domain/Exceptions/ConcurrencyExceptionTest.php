<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Exceptions;

use PHPUnit\Framework\TestCase;
use StoreYar\Shared\Application\Errors\ConcurrencyError;
use StoreYar\Shared\Domain\Exceptions\ConcurrencyException;

final class ConcurrencyExceptionTest extends TestCase
{
    public function test_it_exposes_the_wrapped_concurrency_error(): void
    {
        $error = new ConcurrencyError(
            code: 'identity.user.version_conflict',
            message: 'The user has been modified by another operation.',
            details: [
                'user_id' => '01JTESTUSERID00000000000000',
                'expected_version' => 0,
                'actual_version' => 1,
            ],
        );

        $exception = new ConcurrencyException($error);

        self::assertSame(
            'identity.user.version_conflict',
            $exception->errorCode(),
        );

        self::assertSame(
            'The user has been modified by another operation.',
            $exception->getMessage(),
        );

        self::assertSame(
            0,
            $exception->context()['expected_version'],
        );

        self::assertSame(
            1,
            $exception->context()['actual_version'],
        );
    }
}
