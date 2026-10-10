<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Domain\Contracts;

interface ProductExistenceChecker
{
    public function existsInOrganization(string $organizationId, string $productId): bool;
}
