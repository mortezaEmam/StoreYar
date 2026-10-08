<?php

// CreateProductCommand.php
namespace StoreYar\Modules\Catalog\Application\Commands\CreateProduct;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class CreateProductCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $name,
        public string $sku,
    ) {}
}
