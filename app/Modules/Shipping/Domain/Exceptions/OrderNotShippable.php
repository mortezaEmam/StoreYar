<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Domain\Exceptions;

final class OrderNotShippable extends ShippingDomainException
{
    public function __construct(string $orderId, string $reason)
    {
        parent::__construct(
            sprintf('Order "%s" is not shippable: %s.', $orderId, $reason),
        );
    }
}
