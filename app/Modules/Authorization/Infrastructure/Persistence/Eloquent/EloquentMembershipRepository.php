<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Infrastructure\Persistence\Eloquent;

use StoreYar\Modules\Authorization\Domain\Contracts\MembershipRepository;
use StoreYar\Modules\Authorization\Domain\Entities\Membership;
use StoreYar\Modules\Authorization\Domain\Enums\Role;
use StoreYar\Modules\Authorization\Domain\ValueObjects\MembershipId;
use StoreYar\Shared\Application\Errors\ConcurrencyError;
use StoreYar\Shared\Domain\Exceptions\ConcurrencyException;
use StoreYar\Shared\Infrastructure\Concurrency\DatabaseVersionedUpdater;

final class EloquentMembershipRepository implements MembershipRepository
{
    public function __construct(
        private readonly DatabaseVersionedUpdater $versionedUpdater,
    ) {
    }

    public function findById(MembershipId $id): ?Membership
    {
        $model = MembershipModel::query()->whereKey($id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByOrganizationAndUser(
        string $organizationId,
        string $userId,
    ): ?Membership {
        $model = MembershipModel::query()
            ->where('organization_id', $organizationId)
            ->where('user_id', $userId)
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByOrganizationId(string $organizationId): array
    {
        return MembershipModel::query()
            ->where('organization_id', $organizationId)
            ->orderBy('created_at')
            ->get()
            ->map(fn (MembershipModel $model) => $this->toDomain($model))
            ->all();
    }

    public function findByUserId(string $userId): array
    {
        return MembershipModel::query()
            ->where('user_id', $userId)
            ->orderBy('created_at')
            ->get()
            ->map(fn (MembershipModel $model) => $this->toDomain($model))
            ->all();
    }

    public function save(Membership $membership): void
    {
        $model = MembershipModel::query()
            ->whereKey($membership->membershipId()->value())
            ->first();

        if ($model === null) {
            MembershipModel::query()->create([
                'id' => $membership->membershipId()->value(),
                'organization_id' => $membership->organizationId(),
                'user_id' => $membership->userId(),
                'role' => $membership->role()->value,
                'version' => $membership->version(),
                'created_at' => $membership->createdAt(),
                'updated_at' => $membership->updatedAt(),
            ]);

            return;
        }

        $expectedVersion = $membership->version() - 1;

        if ($expectedVersion < 0) {
            throw new \LogicException(
                'Existing membership version must be greater than zero.',
            );
        }

        $result = $this->versionedUpdater->update(
            table: 'authorization_memberships',
            id: $membership->membershipId()->value(),
            expectedVersion: $expectedVersion,
            changes: [
                'organization_id' => $membership->organizationId(),
                'user_id' => $membership->userId(),
                'role' => $membership->role()->value,
            ],
        );

        if (! $result->updatedSuccessfully()) {
            throw new ConcurrencyException(
                new ConcurrencyError(
                    code: 'authorization.membership.version_conflict',
                    message: 'The membership has been modified by another operation.',
                    details: [
                        'membership_id' => $membership->membershipId()->value(),
                        'expected_version' => $expectedVersion,
                        'actual_version' => $result->nextVersion(),
                    ],
                ),
            );
        }
    }

    private function toDomain(MembershipModel $model): Membership
    {
        return Membership::reconstitute(
            id: MembershipId::fromString($model->getKey()),
            organizationId: $model->organization_id,
            userId: $model->user_id,
            role: Role::from($model->role),
            createdAt: $model->created_at instanceof \DateTimeImmutable
                ? $model->created_at
                : \DateTimeImmutable::createFromInterface($model->created_at),
            updatedAt: $model->updated_at instanceof \DateTimeImmutable
                ? $model->updated_at
                : \DateTimeImmutable::createFromInterface($model->updated_at),
            version: (int) $model->version,
        );
    }
}
