<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent;

use StoreYar\Modules\Identity\Domain\Contracts\PasswordResetTokenRepository;

final class EloquentPasswordResetTokenRepository implements PasswordResetTokenRepository
{
    public function store(string $email, string $tokenHash, \DateTimeImmutable $expiresAt): void
    {
        $email = strtolower($email);

        IdentityPasswordResetTokenModel::query()->updateOrInsert(
            ['email' => $email],
            [
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'created_at' => now()->format('Y-m-d H:i:s'),
            ],
        );
    }

    public function findHashByEmail(string $email): ?string
    {
        $row = IdentityPasswordResetTokenModel::query()
            ->where('email', strtolower($email))
            ->first();

        if ($row === null) {
            return null;
        }

        if ($row->expires_at !== null && $row->expires_at->isPast()) {
            $this->deleteByEmail($email);

            return null;
        }

        return $row->token_hash;
    }

    public function deleteByEmail(string $email): void
    {
        IdentityPasswordResetTokenModel::query()
            ->where('email', strtolower($email))
            ->delete();
    }
}
