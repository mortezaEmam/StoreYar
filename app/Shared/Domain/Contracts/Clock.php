<?php

declare(strict_types=1);

namespace StoreYar\Shared\Domain\Contracts;

interface Clock
{
    public function now(): \DateTimeImmutable;
}
