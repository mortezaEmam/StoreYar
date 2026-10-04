<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Infrastructure\Persistence\Eloquent;

use StoreYar\Modules\Organization\Domain\Aggregates\Organization;
use StoreYar\Modules\Organization\Domain\Contracts\OrganizationRepository;
use StoreYar\Modules\Organization\Domain\Enums\OrganizationStatus;
use StoreYar\Modules\Organization\Domain\ValueObjects\OrganizationId;
use StoreYar\Shared\Application\Errors\ConcurrencyError;
use StoreYar\Shared\Domain\Exceptions\ConcurrencyException;
use StoreYar\Shared\Infrastructure\Concurrency\DatabaseVersionedUpdater;

final class EloquentOrganizationRepository implements OrganizationRepository
{
    public function __construct(
        private readonly DatabaseVersionedUpdater $versionedUpdater,
    ) {
    }

    public function findById(OrganizationId $id): ?Organization
    {
        $model = OrganizationModel::query()
            ->whereKey($id->value())
            ->first();

        return $model === null
            ? null
            : $this->toDomain($model);
    }

    public function findByName(string $name): ?Organization
    {
        $model = OrganizationModel::query()
            ->where('name', $name)
            ->first();

        return $model === null
            ? null
            : $this->toDomain($model);
    }

    public function save(Organization $organization): void
    {
        $model = OrganizationModel::query()
            ->whereKey($organization->organizationId()->value())
            ->first();

        if ($model === null) {
            OrganizationModel::query()->create([
                'id' => $organization->organizationId()->value(),
                'name' => $organization->name(),
                'owner_user_id' => $organization->ownerUserId(),
                'status' => $organization->status()->value,
                'version' => $organization->version(),
                'created_at' => $organization->createdAt(),
                'updated_at' => $organization->updatedAt(),
            ]);

            return;
        }

        $expectedVersion = $organization->version() - 1;

        if ($expectedVersion < 0) {
            throw new \LogicException(
                'Existing organization version must be greater than zero.',
            );
        }

        $result = $this->versionedUpdater->update(
            table: 'organization_organizations',
            id: $organization->organizationId()->value(),
            expectedVersion: $expectedVersion,
            changes: [
                'name' => $organization->name(),
                'owner_user_id' => $organization->ownerUserId(),
                'status' => $organization->status()->value,
            ],
        );

        if (! $result->updatedSuccessfully()) {
            $error = new ConcurrencyError(
                code: 'organization.organization.version_conflict',
                message: 'The organization has been modified by another operation.',
                details: [
                    'organization_id' => $organization->organizationId()->value(),
                    'expected_version' => $expectedVersion,
                    'actual_version' => $result->nextVersion(),
                ],
            );

            throw new ConcurrencyException($error);
        }
    }

    private function toDomain(OrganizationModel $model): Organization
    {
        return Organization::reconstitute(
            id: OrganizationId::fromString($model->getKey()),
            name: $model->name,
            ownerUserId: $model->owner_user_id,
            createdAt: $model->created_at instanceof \DateTimeImmutable
                ? $model->created_at
                : \DateTimeImmutable::createFromInterface($model->created_at),
            updatedAt: $model->updated_at instanceof \DateTimeImmutable
                ? $model->updated_at
                : \DateTimeImmutable::createFromInterface($model->updated_at),
            status: OrganizationStatus::from($model->status),
            version: (int) $model->version,
        );
    }


    public function findByOwnerUserId(string $ownerUserId): array
    {
        return OrganizationModel::query()
            ->where('owner_user_id', $ownerUserId)
            ->orderBy('created_at')
            ->get()
            ->map(fn (OrganizationModel $model) => $this->toDomain($model))
            ->all();
    }
}
