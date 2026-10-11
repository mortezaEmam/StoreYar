<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Infrastructure\Catalog;

use StoreYar\Modules\Catalog\Domain\Contracts\ProductRepository;
use StoreYar\Modules\Catalog\Domain\ValueObjects\ProductId;
use StoreYar\Modules\Sales\Domain\Contracts\ProductCatalogGateway;
use StoreYar\Modules\Sales\Domain\ValueObjects\ProductPricing;

final class CatalogProductCatalogGateway implements ProductCatalogGateway
{
    public function __construct(
        private ProductRepository $products,
    ) {}

    public function existsInOrganization(string $organizationId, string $productId): bool
    {
        return $this->pricingFor($organizationId, $productId) !== null;
    }

    public function pricingFor(string $organizationId, string $productId): ?ProductPricing
    {
        try {
            $product = $this->products->findById(ProductId::fromString($productId));
        } catch (\InvalidArgumentException) {
            return null;
        }

        if ($product === null || $product->organizationId() !== $organizationId) {
            return null;
        }

        return new ProductPricing(
            productId: $product->productId()->value(),
            unitPriceAmount: $product->unitPriceAmount(),
            currency: $product->currency(),
        );
    }
}
