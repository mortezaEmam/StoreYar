<?php

// GetStockByProductQuery.php
namespace StoreYar\Modules\Inventory\Application\Queries\GetStockByProduct;

use StoreYar\Shared\Application\Bus\Query\Query;

final readonly class GetStockByProductQuery implements Query
{
    public function __construct(
        public string $organizationId,
        public string $productId,
    ) {}
}
