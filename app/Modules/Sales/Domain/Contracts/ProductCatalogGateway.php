<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Domain\Contracts;

interface ProductCatalogGateway
{
    public function existsInOrganization(string $organizationId, string $productId): bool;
}
