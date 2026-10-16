<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Application\Commands\ShipShipment;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class ShipShipmentCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $shipmentId,
        public string $trackingNumber,
    ) {
    }
}
