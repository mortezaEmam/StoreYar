<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Domain\Contracts;

use StoreYar\Modules\Sales\Domain\ValueObjects\ProductPricing;

interface ProductCatalogGateway
{
    public function existsInOrganization(string $organizationId, string $productId): bool;

    public function pricingFor(string $organizationId, string $productId): ?ProductPricing;
}
