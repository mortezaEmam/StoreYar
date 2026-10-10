<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Domain\Contracts;

use StoreYar\Modules\Inventory\Domain\Aggregates\StockItem;
use StoreYar\Modules\Inventory\Domain\ValueObjects\StockItemId;

interface StockItemRepository
{
    public function findById(StockItemId $id): ?StockItem;

    public function findByOrganizationAndProduct(
        string $organizationId,
        string $productId,
    ): ?StockItem;

    /**
     * @return list<StockItem>
     */
    public function findByOrganizationId(string $organizationId): array;

    public function save(StockItem $stockItem): void;
}
