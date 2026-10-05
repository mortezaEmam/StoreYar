<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Domain\Enums;

enum BranchStatus: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
