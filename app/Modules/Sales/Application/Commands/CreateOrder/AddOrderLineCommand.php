<?php

namespace StoreYar\Modules\Sales\Application\Commands\CreateOrder;
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
