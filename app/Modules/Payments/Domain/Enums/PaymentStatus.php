<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Domain\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isSucceeded(): bool
    {
        return $this === self::SUCCEEDED;
    }

    public function canComplete(): bool
    {
        return $this === self::PENDING;
    }

    public function canFail(): bool
    {
        return $this === self::PENDING;
    }

    public function canRefund(): bool
    {
        return $this === self::SUCCEEDED;
    }
}
