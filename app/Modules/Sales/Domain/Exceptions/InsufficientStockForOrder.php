<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Domain\Exceptions;

final class InsufficientStockForOrder extends SalesDomainException
{
    public function __construct(
        public readonly string $productId,
        public readonly int $requested,
        public readonly int $available,
    ) {
        parent::__construct(
            sprintf(
                'Insufficient stock for product "%s": requested %d, available %d.',
                $productId,
                $requested,
                $available,
            ),
        );
    }
}
