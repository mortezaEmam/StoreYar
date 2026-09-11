<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Infrastructure\Clock;

use StoreYar\Shared\Infrastructure\Clock\SystemClock;
use Tests\TestCase;

class SystemClockTest extends TestCase
{
    public function test_it_returns_current_time_in_utc(): void
    {
        $clock = new SystemClock();

        $now = $clock->now();

        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $now
        );

        $this->assertSame(
            'UTC',
            $now->getTimezone()->getName()
        );
    }

    public function test_it_returns_a_reasonable_current_time(): void
    {
        $clock = new SystemClock();

        $before = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $now = $clock->now();

        $after = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $this->assertGreaterThanOrEqual(
            $before->getTimestamp(),
            $now->getTimestamp()
        );

        $this->assertLessThanOrEqual(
            $after->getTimestamp(),
            $now->getTimestamp()
        );
    }
}
