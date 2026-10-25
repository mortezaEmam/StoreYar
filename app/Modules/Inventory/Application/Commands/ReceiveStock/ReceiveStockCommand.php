<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Commands\ReceiveStock;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class ReceiveStockCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $warehouseId,
        public string $productId,
        public int $quantity,
        public ?string $reason = null,
        public ?string $documentType = 'STOCK_RECEIPT',
        public ?string $documentId = null,
        public ?string $createdBy = null,
        public ?int $unitCostAmount = null,
        public string $currency = 'IRR',
    ) {}
}
