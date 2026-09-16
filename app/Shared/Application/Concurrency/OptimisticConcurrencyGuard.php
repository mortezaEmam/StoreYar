<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Concurrency;

use StoreYar\Shared\Application\Results\Result;

final class OptimisticConcurrencyGuard
{
    public function check(
        int $expectedVersion,
        int $actualVersion,
    ): Result {
        if ($expectedVersion < 0) {
            return Result::failure(
                errorCode: 'concurrency.invalid_expected_version',
                errorMessage: 'Expected version must be zero or greater.',
            );
        }

        if ($actualVersion < 0) {
            return Result::failure(
                errorCode: 'concurrency.invalid_actual_version',
                errorMessage: 'Actual version must be zero or greater.',
            );
        }

        if ($expectedVersion !== $actualVersion) {
            return Result::failure(
                errorCode: 'concurrency.version_conflict',
                errorMessage: 'The resource has been modified by another operation.',
                data: [
                    'expected_version' => $expectedVersion,
                    'actual_version' => $actualVersion,
                ],
            );
        }

        return Result::success();
    }
}
