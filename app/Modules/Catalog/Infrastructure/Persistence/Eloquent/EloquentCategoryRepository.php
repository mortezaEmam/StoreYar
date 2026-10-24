<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent;

use StoreYar\Modules\Catalog\Domain\Contracts\CategoryRepository;
use StoreYar\Modules\Catalog\Domain\ValueObjects\CategoryId;
use Symfony\Component\Uid\Ulid;

final class EloquentCategoryRepository implements CategoryRepository
{
    public function listByOrganization(string $organizationId): array
    {
        return CategoryModel::query()
            ->where('organization_id', $organizationId)
            ->orderBy('name')
            ->get()
            ->map(fn (CategoryModel $m) => [
                'id' => $m->id,
                'organization_id' => $m->organization_id,
                'name' => $m->name,
            ])
            ->all();
    }

    public function existsInOrganization(CategoryId $id, string $organizationId): bool
    {
        return CategoryModel::query()
            ->whereKey($id->value())
            ->where('organization_id', $organizationId)
            ->exists();
    }

    public function create(string $organizationId, string $name): array
    {
        $id = (string) new Ulid();
        CategoryModel::query()->create([
            'id' => $id,
            'organization_id' => $organizationId,
            'name' => $name,
        ]);

        return [
            'id' => $id,
            'organization_id' => $organizationId,
            'name' => $name,
        ];
    }

    public function findName(CategoryId $id): ?string
    {
        return CategoryModel::query()->whereKey($id->value())->value('name');
    }
}
