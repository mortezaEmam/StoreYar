<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Domain\Contracts;

interface SessionTokenGenerator
{
    public function generate(): string;

    public function hash(string $plainToken): string;
}
