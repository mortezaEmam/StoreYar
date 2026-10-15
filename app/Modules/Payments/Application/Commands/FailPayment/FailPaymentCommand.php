<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Application\Commands\FailPayment;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class FailPaymentCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $paymentId,
        public string $reason = '',
    ) {
    }
}
