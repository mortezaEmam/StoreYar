<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Domain\Exceptions;

use StoreYar\Modules\Payments\Domain\Enums\PaymentStatus;

final class InvalidPaymentState extends PaymentsDomainException
{
    public function __construct(string $action, PaymentStatus $status)
    {
        parent::__construct(
            sprintf('Cannot %s payment in status "%s".', $action, $status->value),
        );
    }
}
