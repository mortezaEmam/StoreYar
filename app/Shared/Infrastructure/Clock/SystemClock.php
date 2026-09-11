<?php

declare(strict_types=1);

namespace StoreYar\Shared\Infrastructure\Clock;

use StoreYar\Shared\Domain\Contracts\Clock;

final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
