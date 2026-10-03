<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Domain\Contracts;

use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use StoreYar\Modules\Identity\Domain\Entities\Session;

interface SessionRepository
{
    public function create(
        SessionId $sessionId,
        string $userId,
        string $tokenHash,
        \DateTimeImmutable $expiresAt,
    ): void;

    public function revoke(SessionId $sessionId): void;


    public function revokeAllForUser(string $userId): void;


    public function findActiveByTokenHash(
        string $tokenHash,
        \DateTimeImmutable $now,
    ): ?SessionId;


    public function findById(SessionId $sessionId): ?Session;
}
