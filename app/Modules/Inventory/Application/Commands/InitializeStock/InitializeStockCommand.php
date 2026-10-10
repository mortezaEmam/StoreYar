<?php

// InitializeStockCommand.php
namespace StoreYar\Modules\Inventory\Application\Commands\InitializeStock;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class InitializeStockCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $productId,
        public int $quantity = 0,
    ) {}
}
