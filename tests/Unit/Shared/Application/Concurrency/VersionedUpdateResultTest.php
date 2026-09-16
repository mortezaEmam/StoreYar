<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Concurrency;

use PHPUnit\Framework\TestCase;
use StoreYar\Shared\Application\Concurrency\VersionedUpdateResult;

final class VersionedUpdateResultTest extends TestCase
{
    public function test_successful_update_contains_next_version(): void
    {
        $result = VersionedUpdateResult::updated(
            nextVersion: 4,
        );

        $this->assertTrue(
            $result->updatedSuccessfully(),
        );

        $this->assertSame(
            4,
            $result->nextVersion(),
        );
    }

    public function test_conflict_contains_current_version(): void
    {
        $result = VersionedUpdateResult::conflict(
            currentVersion: 5,
        );

        $this->assertFalse(
            $result->updatedSuccessfully(),
        );

        $this->assertSame(
            5,
            $result->nextVersion(),
        );
    }
}
