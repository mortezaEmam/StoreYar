<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Domain\Exceptions;

final class DuplicateOrderLine extends SalesDomainException
{
    public function __construct(string $productId)
    {
        parent::__construct(
            sprintf('Product "%s" is already on this order.', $productId),
        );
    }
}
