<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Infrastructure\Catalog;

use StoreYar\Modules\Catalog\Domain\Contracts\ProductRepository;
use StoreYar\Modules\Catalog\Domain\ValueObjects\ProductId;
use StoreYar\Modules\Inventory\Domain\Contracts\ProductExistenceChecker;

final class CatalogProductExistenceChecker implements ProductExistenceChecker
{
    public function __construct(
        private ProductRepository $products,
    ) {}

    public function existsInOrganization(string $organizationId, string $productId): bool
    {
        try {
            $product = $this->products->findById(ProductId::fromString($productId));
        } catch (\InvalidArgumentException) {
            return false;
        }

        return $product !== null
            && $product->organizationId() === $organizationId;
    }
}
