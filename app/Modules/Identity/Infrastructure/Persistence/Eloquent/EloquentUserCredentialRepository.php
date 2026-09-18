<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent;

use StoreYar\Modules\Identity\Domain\Contracts\UserCredentialRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;

final class EloquentUserCredentialRepository implements UserCredentialRepository
{
    public function findPasswordHash(UserId $userId): ?string
    {
        $model = IdentityUserCredentialModel::query()
            ->whereKey($userId->value())
            ->first();

        return $model?->password_hash;
    }

    public function savePasswordHash(
        UserId $userId,
        string $passwordHash,
    ): void {
        IdentityUserCredentialModel::query()->updateOrCreate(
            [
                'user_id' => $userId->value(),
            ],
            [
                'password_hash' => $passwordHash,
            ],
        );
    }
}
