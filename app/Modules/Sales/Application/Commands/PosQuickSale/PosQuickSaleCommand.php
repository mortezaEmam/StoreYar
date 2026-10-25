<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Commands\PosQuickSale;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class PosQuickSaleCommand implements Command
{
    /**
     * @param list<array{product_id?: string, barcode?: string, quantity: int}> $lines
     */
    public function __construct(
        public string $organizationId,
        public string $posSessionId,
        public array $lines,
        public string $paymentMethod = 'CASH',
        public ?string $customerId = null,
        public int $discountAmount = 0,
        public ?string $createdBy = null,
    ) {}
}
