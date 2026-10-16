<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Application\Queries\GetShipmentById;

use StoreYar\Shared\Application\Bus\Query\Query;

final readonly class GetShipmentByIdQuery implements Query
{
    public function __construct(
        public string $organizationId,
        public string $shipmentId,
    ) {
    }
}
