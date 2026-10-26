<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Commands\ReturnPosSale;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class ReturnPosSaleCommand implements Command
{
    /**
     * @param list<array{pos_sale_line_id?: string, product_id: string, quantity: int}> $lines
     */
    public function __construct(
        public string $organizationId,
        public string $posSaleId,
        public array $lines,
        public ?string $reason = null,
        public ?string $createdBy = null,
    ) {}
}
