<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Commands\IssueStock;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class IssueStockCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $warehouseId,
        public string $productId,
        public int $quantity,
        public ?string $reason = null,
        public ?string $documentType = 'POS_SALE',
        public ?string $documentId = null,
        public ?string $createdBy = null,
    ) {}
}
