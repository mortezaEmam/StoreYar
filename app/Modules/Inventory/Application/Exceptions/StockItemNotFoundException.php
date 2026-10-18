<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Exceptions;

final class StockItemNotFoundException extends \RuntimeException
{
    public function __construct(
        public readonly string $productId,
    ) {
        parent::__construct(
            sprintf('Stock item not found for product "%s".', $productId),
        );
    }
}
