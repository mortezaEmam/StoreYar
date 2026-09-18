<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Domain\Contracts;

use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;

interface UserCredentialRepository
{
    public function findPasswordHash(UserId $userId): ?string;

    public function savePasswordHash(
        UserId $userId,
        string $passwordHash,
    ): void;
}
