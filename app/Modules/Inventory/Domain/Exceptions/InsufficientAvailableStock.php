<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Domain\Exceptions;

final class InsufficientAvailableStock extends InventoryDomainException
{
    public function __construct(int $available, int $requested)
    {
        parent::__construct(
            sprintf(
                'Insufficient available stock: available %d, requested %d.',
                $available,
                $requested,
            ),
        );
    }
}
