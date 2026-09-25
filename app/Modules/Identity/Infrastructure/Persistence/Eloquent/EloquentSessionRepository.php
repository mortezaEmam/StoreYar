<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;

final class EloquentSessionRepository implements SessionRepository
{
    public function create(
        SessionId $sessionId,
        string $userId,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
    ): void {
        IdentitySessionModel::query()->create([
            'id' => $sessionId->value(),
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'revoked_at' => null,
        ]);
    }

    public function revoke(SessionId $sessionId): void
    {
        IdentitySessionModel::query()
            ->whereKey($sessionId->value())
            ->update([
                'revoked_at' => new DateTimeImmutable('now'),
            ]);
    }



    public function revokeAllForUser(string $userId): void
    {
        IdentitySessionModel::query()
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => new DateTimeImmutable('now'),
            ]);
    }


    public function findActiveByTokenHash(
        string $tokenHash,
        DateTimeImmutable $now,
    ): ?SessionId {
        $model = IdentitySessionModel::query()
            ->where('token_hash', $tokenHash)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', $now)
            ->first();

        return $model === null
            ? null
            : SessionId::fromString($model->id);
    }
}
