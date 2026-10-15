<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Domain\Contracts;

use StoreYar\Modules\Sales\Domain\Aggregates\Order;
use StoreYar\Modules\Sales\Domain\ValueObjects\OrderId;
use StoreYar\Shared\Application\Pagination\PageRequest;
use StoreYar\Shared\Application\Pagination\PageResult;

interface OrderRepository
{
    public function findById(OrderId $id): ?Order;

    /**
     * @return list<Order>
     */
    public function findByOrganizationId(string $organizationId): array;

    public function save(Order $order): void;


    /**
     * @return PageResult<\StoreYar\Modules\Sales\Domain\Aggregates\Order>
     */
    public function findByOrganizationIdPaginated(
        string $organizationId,
        PageRequest $page,
    ): PageResult;

}
