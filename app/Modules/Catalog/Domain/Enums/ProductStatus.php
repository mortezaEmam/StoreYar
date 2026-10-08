<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Domain\Enums;

enum ProductStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
