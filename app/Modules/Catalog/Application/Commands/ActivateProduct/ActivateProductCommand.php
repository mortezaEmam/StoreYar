<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Commands\ActivateProduct;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class ActivateProductCommand implements Command
{
    public function __construct(
        public string $productId,
        public string $organizationId,
    ) {}
}
