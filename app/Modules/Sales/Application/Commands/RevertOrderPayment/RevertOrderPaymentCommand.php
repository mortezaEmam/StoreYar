<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Commands\RevertOrderPayment;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class RevertOrderPaymentCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $orderId,
        public string $paymentId,
    ) {
    }
}
