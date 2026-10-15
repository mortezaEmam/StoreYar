<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Domain\Exceptions;

final class InsufficientReservedStock extends InventoryDomainException
{
    public function __construct(int $reserved, int $requested)
    {
        parent::__construct(
            sprintf(
                'Insufficient reserved stock: reserved %d, requested %d.',
                $reserved,
                $requested,
            ),
        );
    }
}
