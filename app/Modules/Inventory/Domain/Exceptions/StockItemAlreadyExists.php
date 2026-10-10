<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Domain\Exceptions;

final class StockItemAlreadyExists extends InventoryDomainException
{
    public function __construct(string $productId)
    {
        parent::__construct(
            sprintf('Stock item already exists for product "%s".', $productId),
        );
    }
}
