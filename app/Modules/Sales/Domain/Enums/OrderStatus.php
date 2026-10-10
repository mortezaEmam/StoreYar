<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Domain\Enums;

enum OrderStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';

    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    public function isConfirmed(): bool
    {
        return $this === self::CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    public function canAddLine(): bool
    {
        return $this === self::DRAFT;
    }

    public function canConfirm(): bool
    {
        return $this === self::DRAFT;
    }

    public function canCancel(): bool
    {
        return $this === self::DRAFT || $this === self::CONFIRMED;
    }
}
