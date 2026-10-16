<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Application\Commands\CreateShipment;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class CreateShipmentCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $orderId,
    ) {
    }
}
