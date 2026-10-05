<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Infrastructure\Persistence\Eloquent;

use StoreYar\Modules\Organization\Domain\Contracts\BranchRepository;
use StoreYar\Modules\Organization\Domain\Entities\Branch;
use StoreYar\Modules\Organization\Domain\Enums\BranchStatus;
use StoreYar\Modules\Organization\Domain\ValueObjects\BranchId;
use StoreYar\Modules\Organization\Domain\ValueObjects\OrganizationId;
use StoreYar\Shared\Application\Errors\ConcurrencyError;
use StoreYar\Shared\Domain\Exceptions\ConcurrencyException;
use StoreYar\Shared\Infrastructure\Concurrency\DatabaseVersionedUpdater;

final class EloquentBranchRepository implements BranchRepository
{
    public function __construct(
        private readonly DatabaseVersionedUpdater $versionedUpdater,
    ) {
    }

    public function findById(BranchId $id): ?Branch
    {
        $model = BranchModel::query()
            ->whereKey($id->value())
            ->first();

        return $model === null
            ? null
            : $this->toDomain($model);
    }

    public function findByOrganizationId(OrganizationId $organizationId): array
    {
        return BranchModel::query()
            ->where('organization_id', $organizationId->value())
            ->orderBy('created_at')
            ->get()
            ->map(fn (BranchModel $model) => $this->toDomain($model))
            ->all();
    }

    public function save(Branch $branch): void
    {
        $model = BranchModel::query()
            ->whereKey($branch->branchId()->value())
            ->first();

        if ($model === null) {
            BranchModel::query()->create([
                'id' => $branch->branchId()->value(),
                'organization_id' => $branch->organizationId()->value(),
                'name' => $branch->name(),
                'status' => $branch->status()->value,
                'version' => $branch->version(),
                'created_at' => $branch->createdAt(),
                'updated_at' => $branch->updatedAt(),
            ]);

            return;
        }

        $expectedVersion = $branch->version() - 1;

        if ($expectedVersion < 0) {
            throw new \LogicException(
                'Existing branch version must be greater than zero.',
            );
        }

        $result = $this->versionedUpdater->update(
            table: 'organization_branches',
            id: $branch->branchId()->value(),
            expectedVersion: $expectedVersion,
            changes: [
                'organization_id' => $branch->organizationId()->value(),
                'name' => $branch->name(),
                'status' => $branch->status()->value,
            ],
        );

        if (! $result->updatedSuccessfully()) {
            $error = new ConcurrencyError(
                code: 'organization.branch.version_conflict',
                message: 'The branch has been modified by another operation.',
                details: [
                    'branch_id' => $branch->branchId()->value(),
                    'expected_version' => $expectedVersion,
                    'actual_version' => $result->nextVersion(),
                ],
            );

            throw new ConcurrencyException($error);
        }
    }

    private function toDomain(BranchModel $model): Branch
    {
        return Branch::reconstitute(
            id: BranchId::fromString($model->getKey()),
            organizationId: OrganizationId::fromString($model->organization_id),
            name: $model->name,
            status: BranchStatus::from($model->status),
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
