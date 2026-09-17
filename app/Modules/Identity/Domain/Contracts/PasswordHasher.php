<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Domain\Contracts;

interface PasswordHasher
{
    public function hash(string $plainPassword): string;

    public function verify(
        string $plainPassword,
        string $hashedPassword,
    ): bool;
}
