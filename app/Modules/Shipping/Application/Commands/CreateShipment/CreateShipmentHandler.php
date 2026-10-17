<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Application\Commands\CreateShipment;

use StoreYar\Modules\Shipping\Domain\Aggregates\Shipment;
use StoreYar\Modules\Shipping\Domain\Contracts\ShipmentRepository;
use StoreYar\Modules\Shipping\Domain\Contracts\ShippableOrderGateway;
use StoreYar\Modules\Shipping\Domain\Exceptions\OrderNotShippable;
use StoreYar\Modules\Shipping\Domain\ValueObjects\ShipmentId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\AuditLogger;
use StoreYar\Shared\Domain\Contracts\Clock;

final class CreateShipmentHandler implements CommandHandler
{
    public function __construct(
        private ShipmentRepository $shipments,
        private ShippableOrderGateway $orders,
        private Clock $clock,
        private AuditLogger $audit,

    ) {
    }

    public function handle(Command $command): Shipment
    {
        if (! $command instanceof CreateShipmentCommand) {
            throw new \InvalidArgumentException('Invalid command for CreateShipmentHandler.');
        }

        $order = $this->orders->findShippable(
            $command->organizationId,
            $command->orderId,
        );

        if ($order === null) {
            throw new OrderNotShippable($command->orderId, 'order not found');
        }

        if ($order->status !== 'paid') {
            throw new OrderNotShippable($command->orderId, 'order must be paid');
        }

        $existing = $this->shipments->findByOrderId(
            $command->organizationId,
            $command->orderId,
        );

        if ($existing !== null) {
            throw new OrderNotShippable($command->orderId, 'shipment already exists');
        }

        $shipment = Shipment::create(
            id: ShipmentId::generate(),
            organizationId: $command->organizationId,
            orderId: $command->orderId,
            now: $this->clock->now(),
        );

        $this->shipments->save($shipment);


        $this->audit->log(
            action: 'shipping.shipment.created',
            resourceType: 'shipping.shipment',
            resourceId: $shipment->shipmentId()->value(),
            organizationId: $shipment->organizationId(),
            payload: [
                'order_id' => $shipment->orderId(),
            ],
        );

        return $shipment;
    }
}
