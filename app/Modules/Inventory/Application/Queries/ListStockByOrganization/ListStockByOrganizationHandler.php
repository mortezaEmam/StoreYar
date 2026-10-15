<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Queries\ListStockByOrganization;

use StoreYar\Modules\Inventory\Domain\Contracts\StockItemRepository;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;
use StoreYar\Shared\Application\Pagination\PageResult;

final class ListStockByOrganizationHandler implements QueryHandler
{
    public function __construct(
        private StockItemRepository $stockItems,
    ) {}

    public function handle(Query $query): PageResult
    {
        if (! $query instanceof ListStockByOrganizationQuery) {
            throw new \InvalidArgumentException(
                'ListStockByOrganizationHandler received an invalid query.',
            );
        }

        return $this->stockItems->findByOrganizationIdPaginated(
            $query->organizationId,
            $query->page,
        );
    }
}
