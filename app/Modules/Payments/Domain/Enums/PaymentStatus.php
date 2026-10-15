<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Domain\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function canComplete(): bool
    {
        return $this === self::PENDING;
    }

    public function canFail(): bool
    {
        return $this === self::PENDING;
    }
}
