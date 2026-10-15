<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Application\Commands\InitiatePayment;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class InitiatePaymentCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $orderId,
    ) {
    }
}
