<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Application\Commands\CancelShipment;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class CancelShipmentCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $shipmentId,
        public string $reason = '',
    ) {
    }
}
