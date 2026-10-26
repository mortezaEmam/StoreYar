<?php

declare(strict_types=1);

namespace StoreYar\Modules\Purchasing\Application\Commands\ReturnPurchase;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class ReturnPurchaseCommand implements Command
{
    /**
     * @param list<array{product_id: string, quantity: int, unit_cost_amount?: int}> $lines
     */
    public function __construct(
        public string $organizationId,
        public string $supplierId,
        public string $warehouseId,
        public array $lines,
        public ?string $purchaseOrderId = null,
        public ?string $reason = null,
        public ?string $createdBy = null,
        public bool $postAccounting = true,
    ) {}
}
