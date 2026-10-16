<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Domain\Exceptions;

use StoreYar\Modules\Shipping\Domain\Enums\ShipmentStatus;

final class InvalidShipmentState extends ShippingDomainException
{
    public function __construct(string $action, ShipmentStatus $status)
    {
        parent::__construct(
            sprintf('Cannot %s shipment in status "%s".', $action, $status->value),
        );
    }
}
