<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Application\Commands\CancelShipment;

use StoreYar\Modules\Shipping\Domain\Aggregates\Shipment;
use StoreYar\Modules\Shipping\Domain\Contracts\ShipmentRepository;
use StoreYar\Modules\Shipping\Domain\ValueObjects\ShipmentId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Shared\Application\Bus\Command\CommandHandler;
use StoreYar\Shared\Domain\Contracts\AuditLogger;
use StoreYar\Shared\Domain\Contracts\Clock;

final class CancelShipmentHandler implements CommandHandler
{
    public function __construct(
        private ShipmentRepository $shipments,
        private Clock $clock,
        private AuditLogger $audit,

    ) {
    }

    public function handle(Command $command): Shipment
    {
        if (! $command instanceof CancelShipmentCommand) {
            throw new \InvalidArgumentException(
                'CancelShipmentHandler received an invalid command.',
            );
        }

        $shipment = $this->shipments->findById(ShipmentId::fromString($command->shipmentId));

        if ($shipment === null || $shipment->organizationId() !== $command->organizationId) {
            throw new \InvalidArgumentException('Shipment not found.');
        }

        $shipment->cancel($this->clock->now(), $command->reason);
        $this->shipments->save($shipment);

        $this->audit->log(
            action: 'shipping.shipment.cancelled',
            resourceType: 'shipping.shipment',
            resourceId: $shipment->shipmentId()->value(),
            organizationId: $shipment->organizationId(),
            payload: [
                'order_id' => $shipment->orderId(),
                'reason' => $command->reason,
            ],
        );

        return $shipment;
    }
}
