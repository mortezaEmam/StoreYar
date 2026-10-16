<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Domain\Contracts;

use StoreYar\Modules\Shipping\Domain\Aggregates\Shipment;
use StoreYar\Modules\Shipping\Domain\ValueObjects\ShipmentId;
use StoreYar\Shared\Application\Pagination\PageRequest;
use StoreYar\Shared\Application\Pagination\PageResult;

interface ShipmentRepository
{
    public function findById(ShipmentId $id): ?Shipment;

    public function findByOrderId(string $organizationId, string $orderId): ?Shipment;

    /**
     * @return PageResult<Shipment>
     */
    public function findByOrganizationIdPaginated(
        string $organizationId,
        PageRequest $page,
    ): PageResult;

    public function save(Shipment $shipment): void;
}
