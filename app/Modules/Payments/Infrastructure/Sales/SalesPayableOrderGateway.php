<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Infrastructure\Sales;

use StoreYar\Modules\Payments\Domain\Contracts\PayableOrder;
use StoreYar\Modules\Payments\Domain\Contracts\PayableOrderGateway;
use StoreYar\Modules\Sales\Domain\Contracts\OrderRepository;
use StoreYar\Modules\Sales\Domain\ValueObjects\OrderId;

final class SalesPayableOrderGateway implements PayableOrderGateway
{
    public function __construct(
        private OrderRepository $orders,
    ) {
    }

    public function findPayable(string $organizationId, string $orderId): ?PayableOrder
    {
        try {
            $order = $this->orders->findById(OrderId::fromString($orderId));
        } catch (\InvalidArgumentException) {
            return null;
        }

        if ($order === null || $order->organizationId() !== $organizationId) {
            return null;
        }

        $total = $order->total();

        return new PayableOrder(
            orderId: $order->orderId()->value(),
            organizationId: $order->organizationId(),
            status: $order->status()->value,
            totalAmount: $total->amount(),
            currency: $total->currency(),
        );
    }
}
