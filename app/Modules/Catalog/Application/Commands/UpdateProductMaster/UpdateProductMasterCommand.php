<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Commands\UpdateProductMaster;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class UpdateProductMasterCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $productId,
        public ?string $shortName = null,
        public ?string $description = null,
        public ?string $brandId = null,
        public ?string $categoryId = null,
        public ?string $baseUnitId = null,
        public ?string $saleUnitId = null,
        public ?string $purchaseUnitId = null,
        public ?int $costPriceAmount = null,
        public ?bool $isSellable = null,
        public ?bool $isPurchasable = null,
        public ?bool $isStockable = null,
        public ?bool $isTaxable = null,
        public ?float $minStock = null,
        public ?float $maxStock = null,
        public ?float $reorderPoint = null,
        public ?float $reorderQty = null,
        public ?bool $trackLot = null,
        public ?bool $trackSerial = null,
        public ?bool $trackExpiry = null,
    ) {}
}
