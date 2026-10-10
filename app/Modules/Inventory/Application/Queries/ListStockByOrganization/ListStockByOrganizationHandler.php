<?php

declare(strict_types=1);

// ListStockByOrganizationHandler.php
namespace StoreYar\Modules\Inventory\Application\Queries\ListStockByOrganization;

use StoreYar\Modules\Inventory\Domain\Contracts\StockItemRepository;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;

final class ListStockByOrganizationHandler implements QueryHandler
{
    public function __construct(
        private StockItemRepository $stockItems,
    ) {}

    public function handle(Query $query): array
    {
        if (! $query instanceof ListStockByOrganizationQuery) {
            throw new \InvalidArgumentException(
                'ListStockByOrganizationHandler received an invalid query.',
            );
        }

        return $this->stockItems->findByOrganizationId($query->organizationId);
    }
}
