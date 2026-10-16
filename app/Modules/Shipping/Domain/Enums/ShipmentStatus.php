<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Domain\Enums;

enum ShipmentStatus: string
{
    case PENDING = 'pending';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function canShip(): bool
    {
        return $this === self::PENDING;
    }

    public function canDeliver(): bool
    {
        return $this === self::SHIPPED;
    }

    public function canCancel(): bool
    {
        return $this === self::PENDING || $this === self::SHIPPED;
    }
}
