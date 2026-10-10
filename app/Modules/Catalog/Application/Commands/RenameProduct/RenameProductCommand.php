<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Commands\RenameProduct;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class RenameProductCommand implements Command
{
    public function __construct(
        public string $productId,
        public string $organizationId,
        public string $name,
    ) {}
}
