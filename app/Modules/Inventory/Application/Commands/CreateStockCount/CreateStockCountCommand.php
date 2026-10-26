<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Commands\CreateStockCount;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class CreateStockCountCommand implements Command
{
    /**
     * @param list<array{product_id: string, counted_quantity: int}> $lines
     */
    public function __construct(
        public string $organizationId,
        public string $warehouseId,
        public array $lines,
        public ?string $notes = null,
        public ?string $createdBy = null,
    ) {}
}
