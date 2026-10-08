<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Domain\Contracts;

use StoreYar\Modules\Catalog\Domain\Aggregates\Product;
use StoreYar\Modules\Catalog\Domain\ValueObjects\ProductId;

interface ProductRepository
{
    public function findById(ProductId $id): ?Product;

    public function findByOrganizationAndSku(
        string $organizationId,
        string $sku,
    ): ?Product;

    /**
     * @return list<Product>
     */
    public function findByOrganizationId(string $organizationId): array;

    public function save(Product $product): void;
}
