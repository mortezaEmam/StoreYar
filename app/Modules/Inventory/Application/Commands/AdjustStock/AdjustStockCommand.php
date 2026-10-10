<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Commands\AdjustStock;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class AdjustStockCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $productId,
        public int $amount,
        /** @var 'increase'|'decrease' */
        public string $direction,
    ) {}
}
