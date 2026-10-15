<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Domain\Contracts;

interface PayableOrderGateway
{
    public function findPayable(string $organizationId, string $orderId): ?PayableOrder;
}
