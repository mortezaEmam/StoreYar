<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Commands\TransferStock;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class TransferStockCommand implements Command
{
    /**
     * @param list<array{product_id: string, quantity: int}> $lines
     */
    public function __construct(
        public string $organizationId,
        public string $fromWarehouseId,
        public string $toWarehouseId,
        public array $lines,
        public ?string $notes = null,
        public ?string $createdBy = null,
    ) {}
}
