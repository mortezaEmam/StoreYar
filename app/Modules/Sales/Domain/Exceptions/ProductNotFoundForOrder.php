<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Domain\Exceptions;

final class ProductNotFoundForOrder extends SalesDomainException
{
    public function __construct(string $productId)
    {
        parent::__construct(
            sprintf('Product "%s" was not found in this organization.', $productId),
        );
    }
}
