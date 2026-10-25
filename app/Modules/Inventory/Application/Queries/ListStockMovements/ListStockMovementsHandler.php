<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Application\Queries\ListStockMovements;

use StoreYar\Modules\Inventory\Infrastructure\Persistence\Eloquent\StockMovementModel;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;

final class ListStockMovementsHandler implements QueryHandler
{
    public function handle(Query $query): array
    {
        if (! $query instanceof ListStockMovementsQuery) {
            throw new \InvalidArgumentException('Invalid query.');
        }

        $q = StockMovementModel::query()
            ->where('organization_id', $query->organizationId)
            ->orderByDesc('occurred_at')
            ->limit(min(200, max(1, $query->limit)));

        if ($query->warehouseId !== null) {
            $q->where('warehouse_id', $query->warehouseId);
        }
        if ($query->productId !== null) {
            $q->where('product_id', $query->productId);
        }

        return $q->get()->all();
    }
}
