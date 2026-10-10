<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Commands\ConfirmOrder;

use Illuminate\Database\DatabaseManager;
use StoreYar\Modules\Sales\Domain\Aggregates\Order;
use StoreYar\Modules\Sales\Domain\Contracts\OrderRepository;
use StoreYar\Modules\Sales\Domain\Contracts\StockReservationService;
use StoreYar\Modules\Sales\Domain\ValueObjects\OrderId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final class ConfirmOrderHandler implements CommandHandler
{
    public function __construct(
        private OrderRepository $orders,
        private StockReservationService $stock,
        private Clock $clock,
        private DatabaseManager $db,
    ) {}

    public function handle(Command $command): Order
    {
        if (! $command instanceof ConfirmOrderCommand) {
            throw new \InvalidArgumentException(
                'ConfirmOrderHandler received an invalid command.',
            );
        }

        return $this->db->connection()->transaction(function () use ($command): Order {
            $order = $this->orders->findById(OrderId::fromString($command->orderId));

            if ($order === null || $order->organizationId() !== $command->organizationId) {
                throw new \InvalidArgumentException('Order not found.');
            }

            $lines = [];
            foreach ($order->lines() as $line) {
                $lines[] = [
                    'product_id' => $line->productId(),
                    'quantity' => $line->quantity(),
                ];
            }

            $this->stock->consumeForOrder($command->organizationId, $lines);

            $order->confirm($this->clock->now());
            $this->orders->save($order);

            return $order;
        });
    }
}
