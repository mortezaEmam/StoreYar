<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Domain\Contracts;

use StoreYar\Modules\Catalog\Domain\Aggregates\Product;
use StoreYar\Modules\Catalog\Domain\ValueObjects\ProductId;
use StoreYar\Shared\Application\Pagination\PageRequest;
use StoreYar\Shared\Application\Pagination\PageResult;

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


    /**
     * @return PageResult<\StoreYar\Modules\Catalog\Domain\Aggregates\Product>
     */
    public function findByOrganizationIdPaginated(
        string $organizationId,
        PageRequest $page,
    ): PageResult;
}
