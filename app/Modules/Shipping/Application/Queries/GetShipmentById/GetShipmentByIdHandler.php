<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Application\Queries\GetShipmentById;

use StoreYar\Modules\Shipping\Domain\Aggregates\Shipment;
use StoreYar\Modules\Shipping\Domain\Contracts\ShipmentRepository;
use StoreYar\Modules\Shipping\Domain\ValueObjects\ShipmentId;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;

final class GetShipmentByIdHandler implements QueryHandler
{
    public function __construct(private ShipmentRepository $shipments)
    {
    }

    public function handle(Query $query): ?Shipment
    {
        if (! $query instanceof GetShipmentByIdQuery) {
            throw new \InvalidArgumentException('Invalid query.');
        }

        $shipment = $this->shipments->findById(ShipmentId::fromString($query->shipmentId));

        if ($shipment === null || $shipment->organizationId() !== $query->organizationId) {
            return null;
        }

        return $shipment;
    }
}
