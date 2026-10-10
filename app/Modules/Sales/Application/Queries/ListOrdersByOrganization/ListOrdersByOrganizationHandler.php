<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Queries\ListOrdersByOrganization;

use StoreYar\Modules\Sales\Domain\Contracts\OrderRepository;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;

final class ListOrdersByOrganizationHandler implements QueryHandler
{
    public function __construct(
        private OrderRepository $orders,
    ) {}

    public function handle(Query $query): array
    {
        if (! $query instanceof ListOrdersByOrganizationQuery) {
            throw new \InvalidArgumentException(
                'ListOrdersByOrganizationHandler received an invalid query.',
            );
        }

        return $this->orders->findByOrganizationId($query->organizationId);
    }
}
