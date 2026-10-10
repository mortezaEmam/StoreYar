<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Commands\AddOrderLine;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class AddOrderLineCommand implements Command
{
    public function __construct(
        public string $orderId,
        public string $organizationId,
        public string $productId,
        public int $quantity,
        public int $unitPriceAmount,
        public string $currency = 'IRR',
    ) {}
}
