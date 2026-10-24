<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Domain\Contracts;

use StoreYar\Modules\Catalog\Domain\ValueObjects\CategoryId;

interface CategoryRepository
{
    /**
     * @return list<array{id: string, organization_id: string, name: string}>
     */
    public function listByOrganization(string $organizationId): array;

    public function existsInOrganization(CategoryId $id, string $organizationId): bool;

    public function create(string $organizationId, string $name): array;

    public function findName(CategoryId $id): ?string;
}
