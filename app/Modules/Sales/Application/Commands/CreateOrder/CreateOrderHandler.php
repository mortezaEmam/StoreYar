<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Commands\CreateOrder;

use StoreYar\Modules\Sales\Domain\Aggregates\Order;
use StoreYar\Modules\Sales\Domain\Contracts\OrderRepository;
use StoreYar\Modules\Sales\Domain\ValueObjects\OrderId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final class CreateOrderHandler implements CommandHandler
{
    public function __construct(
        private OrderRepository $orders,
        private Clock $clock,
    ) {}

    public function handle(Command $command): Order
    {
        if (! $command instanceof CreateOrderCommand) {
            throw new \InvalidArgumentException(
                'CreateOrderHandler received an invalid command.',
            );
        }

        $order = Order::create(
            id: OrderId::generate(),
            organizationId: $command->organizationId,
            customerUserId: $command->customerUserId,
            now: $this->clock->now(),
        );

        $this->orders->save($order);

        return $order;
    }
}
