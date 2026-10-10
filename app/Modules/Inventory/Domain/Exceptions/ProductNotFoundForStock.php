<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Domain\Exceptions;

final class ProductNotFoundForStock extends InventoryDomainException
{
    public function __construct(string $productId)
    {
        parent::__construct(
            sprintf('Product "%s" was not found in this organization.', $productId),
        );
    }
}
