<?php

declare(strict_types=1);

namespace StoreYar\Modules\Purchasing\Application\Commands\ReceiveAgainstPO;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class ReceiveAgainstPOCommand implements Command
{
    /**
     * @param list<array{purchase_order_line_id: string, quantity: int}> $lines
     */
    public function __construct(
        public string $organizationId,
        public string $purchaseOrderId,
        public array $lines,
        public ?string $notes = null,
        public ?string $receivedBy = null,
    ) {}
}
