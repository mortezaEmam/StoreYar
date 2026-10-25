<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Queries\ListWarehouses;

use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\WarehouseModel;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;

final class ListWarehousesHandler implements QueryHandler
{
    public function handle(Query $query): array
    {
        if (! $query instanceof ListWarehousesQuery) {
            throw new \InvalidArgumentException('Invalid query for ListWarehousesHandler.');
        }

        return WarehouseModel::query()
            ->where('organization_id', $query->organizationId)
            ->orderBy('code')
            ->get()
            ->all();
    }
}
