<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Exceptions;

final class InsufficientAvailableStockException extends \RuntimeException
{
    public function __construct(
        public readonly string $productId,
        public readonly int $requested,
        public readonly int $available,
    ) {
        parent::__construct(
            sprintf(
                'Insufficient available stock for product "%s": requested %d, available %d.',
                $productId,
                $requested,
                $available,
            ),
        );
    }
}
