<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Domain\Contracts;

interface OrderPaymentService
{
    public function markOrderPaid(
        string $organizationId,
        string $orderId,
        string $paymentId,
    ): void;
}
