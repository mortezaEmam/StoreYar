<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Application\Commands\ShipShipment;

use StoreYar\Modules\Shipping\Domain\Aggregates\Shipment;
use StoreYar\Modules\Shipping\Domain\Contracts\ShipmentRepository;
use StoreYar\Modules\Shipping\Domain\ValueObjects\ShipmentId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\Clock;

final class ShipShipmentHandler implements CommandHandler
{
    public function __construct(
        private ShipmentRepository $shipments,
        private Clock $clock,
    ) {
    }

    public function handle(Command $command): Shipment
    {
        if (! $command instanceof ShipShipmentCommand) {
            throw new \InvalidArgumentException(
                'Invalid command for ShipShipmentHandler.',
            );
        }

        $shipment = $this->shipments->findById(
            ShipmentId::fromString($command->shipmentId),
        );

        if (
            $shipment === null
            || $shipment->organizationId() !== $command->organizationId
        ) {
            throw new \InvalidArgumentException('Shipment not found.');
        }

        $shipment->markShipped(
            $command->trackingNumber,
            $this->clock->now(),
        );

        $this->shipments->save($shipment);

        return $shipment;
    }
}
