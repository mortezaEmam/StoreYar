<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Queries\GetProductById;

use StoreYar\Modules\Catalog\Domain\Aggregates\Product;
use StoreYar\Modules\Catalog\Domain\Contracts\ProductRepository;
use StoreYar\Modules\Catalog\Domain\ValueObjects\ProductId;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;

final class GetProductByIdHandler implements QueryHandler
{
    public function __construct(
        private ProductRepository $products,
    ) {}

    public function handle(Query $query): ?Product
    {
        if (! $query instanceof GetProductByIdQuery) {
            throw new \InvalidArgumentException(
                'GetProductByIdHandler received an invalid query.',
            );
        }

        return $this->products->findById(
            ProductId::fromString($query->productId),
        );
    }
}
