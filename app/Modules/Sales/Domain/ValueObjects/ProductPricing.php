<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Domain\ValueObjects;

final readonly class ProductPricing
{
    public function __construct(
        public string $productId,
        public int $unitPriceAmount,
        public string $currency,
    ) {}
}
