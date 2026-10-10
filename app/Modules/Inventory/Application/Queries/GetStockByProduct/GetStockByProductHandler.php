<?php

declare(strict_types=1);
// GetStockByProductHandler.php
namespace StoreYar\Modules\Inventory\Application\Queries\GetStockByProduct;

use StoreYar\Modules\Inventory\Domain\Aggregates\StockItem;
use StoreYar\Modules\Inventory\Domain\Contracts\StockItemRepository;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;

final class GetStockByProductHandler implements QueryHandler
{
    public function __construct(
        private StockItemRepository $stockItems,
    ) {}

    public function handle(Query $query): ?StockItem
    {
        if (! $query instanceof GetStockByProductQuery) {
            throw new \InvalidArgumentException(
                'GetStockByProductHandler received an invalid query.',
            );
        }

        return $this->stockItems->findByOrganizationAndProduct(
            $query->organizationId,
            $query->productId,
        );
    }
}
