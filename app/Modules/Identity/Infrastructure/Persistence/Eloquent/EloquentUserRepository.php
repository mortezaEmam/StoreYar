<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent;

use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Modules\Identity\Domain\Contracts\UserRepository;
use StoreYar\Modules\Identity\Domain\Enums\UserStatus;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;
use StoreYar\Shared\Application\Errors\ConcurrencyError;
use StoreYar\Shared\Domain\Exceptions\ConcurrencyException;
use StoreYar\Shared\Infrastructure\Concurrency\DatabaseVersionedUpdater;

final class EloquentUserRepository implements UserRepository
{
    public function __construct(
        private readonly DatabaseVersionedUpdater $versionedUpdater,
    ) {
    }

    public function findById(UserId $id): ?User
    {
        $model = IdentityUserModel::query()
            ->whereKey($id->value())
            ->first();

        return $model === null
            ? null
            : $this->toDomain($model);
    }

    public function findByEmail(string $email): ?User
    {
        $model = IdentityUserModel::query()
            ->where('email', $email)
            ->first();

        return $model === null
            ? null
            : $this->toDomain($model);
    }

    public function save(User $user): void
    {
        $model = IdentityUserModel::query()
            ->whereKey($user->id())
            ->first();

        if ($model === null) {
            IdentityUserModel::query()->create([
                'id' => $user->id(),
                'email' => $user->email(),
                'name' => $user->name(),
                'status' => $user->status()->value,
                'version' => $user->version(),
                'created_at' => $user->createdAt(),
                'updated_at' => $user->updatedAt(),
            ]);

            return;
        }

        $expectedVersion = $user->version() - 1;

        if ($expectedVersion < 0) {
            throw new \LogicException(
                'Existing user version must be greater than zero.',
            );
        }

        $result = $this->versionedUpdater->update(
            table: 'identity_users',
            id: $user->id(),
            expectedVersion: $expectedVersion,
            changes: [
                'email' => $user->email(),
                'name' => $user->name(),
                'status' => $user->status()->value,
            ],
        );

        if (! $result->updatedSuccessfully()) {
            $error = new ConcurrencyError(
                code: 'identity.user.version_conflict',
                message: 'The user has been modified by another operation.',
                details: [
                    'user_id' => $user->id(),
                    'expected_version' => $expectedVersion,
                    'actual_version' => $result->nextVersion(),
                ],
            );

            throw new ConcurrencyException($error);
        }
    }

    private function toDomain(IdentityUserModel $model): User
    {
        return User::reconstitute(
            id: UserId::fromString($model->getKey()),
            email: $model->email,
            name: $model->name,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
            status: UserStatus::from($model->status),
            version: $model->version,
        );
    }
}
