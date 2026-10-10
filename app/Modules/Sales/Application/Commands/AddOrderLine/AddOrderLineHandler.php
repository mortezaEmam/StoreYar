<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Commands\AddOrderLine;

use StoreYar\Modules\Sales\Domain\Aggregates\Order;
use StoreYar\Modules\Sales\Domain\Contracts\OrderRepository;
use StoreYar\Modules\Sales\Domain\Contracts\ProductCatalogGateway;
use StoreYar\Modules\Sales\Domain\Exceptions\ProductNotFoundForOrder;
use StoreYar\Modules\Sales\Domain\ValueObjects\Money;
use StoreYar\Modules\Sales\Domain\ValueObjects\OrderId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final class AddOrderLineHandler implements CommandHandler
{
    public function __construct(
        private OrderRepository $orders,
        private ProductCatalogGateway $products,
        private Clock $clock,
    ) {}

    public function handle(Command $command): Order
    {
        if (! $command instanceof AddOrderLineCommand) {
            throw new \InvalidArgumentException(
                'AddOrderLineHandler received an invalid command.',
            );
        }

        $order = $this->orders->findById(OrderId::fromString($command->orderId));

        if ($order === null || $order->organizationId() !== $command->organizationId) {
            throw new \InvalidArgumentException('Order not found.');
        }

        if (! $this->products->existsInOrganization(
            $command->organizationId,
            $command->productId,
        )) {
            throw new ProductNotFoundForOrder($command->productId);
        }

        $order->addLine(
            productId: $command->productId,
            quantity: $command->quantity,
            unitPrice: Money::of($command->unitPriceAmount, $command->currency),
            now: $this->clock->now(),
        );

        $this->orders->save($order);

        return $order;
    }
}
