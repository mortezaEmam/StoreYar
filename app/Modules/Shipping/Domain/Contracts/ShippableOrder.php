<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Domain\Contracts;

final readonly class ShippableOrder
{
    public function __construct(
        public string $orderId,
        public string $organizationId,
        public string $status,
    ) {
    }
}
