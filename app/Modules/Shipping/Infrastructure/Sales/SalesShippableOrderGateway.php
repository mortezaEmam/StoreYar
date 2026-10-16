<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Infrastructure\Sales;

use StoreYar\Modules\Sales\Domain\Contracts\OrderRepository;
use StoreYar\Modules\Sales\Domain\ValueObjects\OrderId;
use StoreYar\Modules\Shipping\Domain\Contracts\ShippableOrder;
use StoreYar\Modules\Shipping\Domain\Contracts\ShippableOrderGateway;

final class SalesShippableOrderGateway implements ShippableOrderGateway
{
    public function __construct(
        private OrderRepository $orders,
    ) {
    }

    public function findShippable(string $organizationId, string $orderId): ?ShippableOrder
    {
        try {
            $order = $this->orders->findById(OrderId::fromString($orderId));
        } catch (\InvalidArgumentException) {
            return null;
        }

        if ($order === null || $order->organizationId() !== $organizationId) {
            return null;
        }

        return new ShippableOrder(
            orderId: $order->orderId()->value(),
            organizationId: $order->organizationId(),
            status: $order->status()->value,
        );
    }
}
