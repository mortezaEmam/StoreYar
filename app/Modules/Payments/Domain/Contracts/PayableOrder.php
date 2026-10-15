<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Domain\Contracts;

final readonly class PayableOrder
{
    public function __construct(
        public string $orderId,
        public string $organizationId,
        public string $status,
        public int $totalAmount,
        public string $currency,
    ) {
    }
}
