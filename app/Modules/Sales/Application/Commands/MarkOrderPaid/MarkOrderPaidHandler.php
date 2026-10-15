<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Commands\MarkOrderPaid;

use StoreYar\Modules\Sales\Domain\Aggregates\Order;
use StoreYar\Modules\Sales\Domain\Contracts\OrderRepository;
use StoreYar\Modules\Sales\Domain\ValueObjects\OrderId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final class MarkOrderPaidHandler implements CommandHandler
{
    public function __construct(
        private OrderRepository $orders,
        private Clock $clock,
    ) {
    }

    public function handle(Command $command): Order
    {
        if (! $command instanceof MarkOrderPaidCommand) {
            throw new \InvalidArgumentException(
                'MarkOrderPaidHandler received an invalid command.',
            );
        }

        $order = $this->orders->findById(OrderId::fromString($command->orderId));

        if ($order === null || $order->organizationId() !== $command->organizationId) {
            throw new \InvalidArgumentException('Order not found.');
        }

        $order->markPaid($command->paymentId, $this->clock->now());
        $this->orders->save($order);

        return $order;
    }
}
