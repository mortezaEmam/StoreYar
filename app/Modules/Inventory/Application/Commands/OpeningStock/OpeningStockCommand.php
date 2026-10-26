<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Commands\OpeningStock;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class OpeningStockCommand implements Command
{
    /**
     * @param list<array{product_id: string, quantity: int, unit_cost_amount?: int}> $lines
     */
    public function __construct(
        public string $organizationId,
        public string $warehouseId,
        public array $lines,
        public ?string $notes = null,
        public ?string $createdBy = null,
    ) {}
}
