<?php

declare(strict_types=1);

namespace StoreYar\Modules\Purchasing\Application\Commands\CreatePurchaseOrder;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class CreatePurchaseOrderCommand implements Command
{
    /**
     * @param list<array{product_id: string, quantity: int, unit_cost_amount?: int}> $lines
     */
    public function __construct(
        public string $organizationId,
        public string $supplierId,
        public string $warehouseId,
        public array $lines,
        public ?string $branchId = null,
        public ?string $notes = null,
        public ?string $expectedAt = null,
        public string $currency = 'IRR',
        public ?string $createdBy = null,
        public bool $submit = true,
    ) {}
}
