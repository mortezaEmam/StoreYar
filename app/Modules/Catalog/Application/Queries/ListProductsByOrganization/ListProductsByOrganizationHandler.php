<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Queries\ListProductsByOrganization;

use StoreYar\Modules\Catalog\Domain\Contracts\ProductRepository;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;
use StoreYar\Shared\Application\Pagination\PageResult;

final class ListProductsByOrganizationHandler implements QueryHandler
{
    public function __construct(
        private ProductRepository $products,
    ) {}

    public function handle(Query $query): PageResult
    {
        if (! $query instanceof ListProductsByOrganizationQuery) {
            throw new \InvalidArgumentException(
                'ListProductsByOrganizationHandler received an invalid query.',
            );
        }

        return $this->products->findByOrganizationIdPaginated(
            $query->organizationId,
            $query->page,
        );
    }
}
