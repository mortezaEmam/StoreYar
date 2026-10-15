<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Commands\MarkOrderPaid;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class MarkOrderPaidCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $orderId,
        public string $paymentId,
    ) {
    }
}
