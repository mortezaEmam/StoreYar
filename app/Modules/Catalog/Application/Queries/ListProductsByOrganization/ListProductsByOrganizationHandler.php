<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Queries\ListProductsByOrganization;

use StoreYar\Modules\Catalog\Domain\Contracts\ProductRepository;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;

final class ListProductsByOrganizationHandler implements QueryHandler
{
    public function __construct(
        private ProductRepository $products,
    ) {}

    /**
     * @return list<\StoreYar\Modules\Catalog\Domain\Aggregates\Product>
     */
    public function handle(Query $query): array
    {
        if (! $query instanceof ListProductsByOrganizationQuery) {
            throw new \InvalidArgumentException(
                'ListProductsByOrganizationHandler received an invalid query.',
            );
        }

        return $this->products->findByOrganizationId($query->organizationId);
    }
}
