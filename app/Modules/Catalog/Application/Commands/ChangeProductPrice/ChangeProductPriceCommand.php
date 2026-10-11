<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Commands\ChangeProductPrice;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class ChangeProductPriceCommand implements Command
{
    public function __construct(
        public string $productId,
        public string $organizationId,
        public int $unitPriceAmount,
        public string $currency = 'IRR',
    ) {}
}
