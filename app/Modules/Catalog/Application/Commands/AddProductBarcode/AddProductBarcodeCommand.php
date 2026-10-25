<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Commands\AddProductBarcode;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class AddProductBarcodeCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $productId,
        public string $barcode,
        public string $type = 'INTERNAL',
        public bool $isPrimary = false,
        public ?string $unitId = null,
        public ?string $variantId = null,
    ) {}
}
