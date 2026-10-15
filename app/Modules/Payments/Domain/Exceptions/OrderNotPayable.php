<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Domain\Exceptions;

final class OrderNotPayable extends PaymentsDomainException
{
    public function __construct(string $orderId, string $reason)
    {
        parent::__construct(
            sprintf('Order "%s" is not payable: %s.', $orderId, $reason),
        );
    }
}
