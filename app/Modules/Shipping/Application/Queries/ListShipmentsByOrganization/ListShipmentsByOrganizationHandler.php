<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Application\Queries\ListShipmentsByOrganization;

use StoreYar\Modules\Shipping\Domain\Contracts\ShipmentRepository;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;
use StoreYar\Shared\Application\Pagination\PageResult;

final class ListShipmentsByOrganizationHandler implements QueryHandler
{
    public function __construct(private ShipmentRepository $shipments)
    {
    }

    public function handle(Query $query): PageResult
    {
        if (! $query instanceof ListShipmentsByOrganizationQuery) {
            throw new \InvalidArgumentException('Invalid query.');
        }

        return $this->shipments->findByOrganizationIdPaginated(
            $query->organizationId,
            $query->page,
        );
    }
}
