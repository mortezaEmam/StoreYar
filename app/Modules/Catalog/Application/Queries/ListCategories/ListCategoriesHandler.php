<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Application\Queries\ListCategories;

use StoreYar\Modules\Catalog\Domain\Contracts\CategoryRepository;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;

final class ListCategoriesHandler implements QueryHandler
{
    public function __construct(private CategoryRepository $categories) {}

    public function handle(Query $query): array
    {
        if (! $query instanceof ListCategoriesQuery) {
            throw new \InvalidArgumentException('Invalid query.');
        }

        return $this->categories->listByOrganization($query->organizationId);
    }
}
