<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Application\Commands\CompletePayment;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class CompletePaymentCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $paymentId,
    ) {
    }
}
