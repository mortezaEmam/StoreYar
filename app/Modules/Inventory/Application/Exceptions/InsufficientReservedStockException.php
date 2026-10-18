<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Exceptions;

final class InsufficientReservedStockException extends \RuntimeException
{
    public function __construct(
        public readonly string $productId,
        public readonly int $requested,
        public readonly int $reserved,
    ) {
        parent::__construct(
            sprintf(
                'Insufficient reserved stock for product "%s": requested %d, reserved %d.',
                $productId,
                $requested,
                $reserved,
            ),
        );
    }
}
