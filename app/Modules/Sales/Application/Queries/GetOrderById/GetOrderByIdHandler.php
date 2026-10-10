<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Queries\GetOrderById;

use StoreYar\Modules\Sales\Domain\Aggregates\Order;
use StoreYar\Modules\Sales\Domain\Contracts\OrderRepository;
use StoreYar\Modules\Sales\Domain\ValueObjects\OrderId;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;

final class GetOrderByIdHandler implements QueryHandler
{
    public function __construct(
        private OrderRepository $orders,
    ) {}

    public function handle(Query $query): ?Order
    {
        if (! $query instanceof GetOrderByIdQuery) {
            throw new \InvalidArgumentException(
                'GetOrderByIdHandler received an invalid query.',
            );
        }

        $order = $this->orders->findById(OrderId::fromString($query->orderId));

        if ($order === null || $order->organizationId() !== $query->organizationId) {
            return null;
        }

        return $order;
    }
}
