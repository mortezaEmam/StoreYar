<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Infrastructure\Security;

use Illuminate\Support\Str;
use StoreYar\Modules\Identity\Domain\Contracts\SessionTokenGenerator;

final class LaravelSessionTokenGenerator implements SessionTokenGenerator
{
    public function generate(): string
    {
        return Str::random(64);
    }

    public function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
