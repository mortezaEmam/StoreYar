<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Domain\Contracts;

interface ShippableOrderGateway
{
    public function findShippable(string $organizationId, string $orderId): ?ShippableOrder;
}
