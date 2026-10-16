<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Presentation\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use StoreYar\Modules\Shipping\Domain\Aggregates\Shipment;

/** @mixin Shipment */
final class ShipmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Shipment $shipment */
        $shipment = $this->resource;

        return [
            'id' => $shipment->shipmentId()->value(),
            'organization_id' => $shipment->organizationId(),
            'order_id' => $shipment->orderId(),
            'status' => $shipment->status()->value,
            'tracking_number' => $shipment->trackingNumber(),
            'created_at' => $shipment->createdAt()->format(DateTimeInterface::ATOM),
            'updated_at' => $shipment->updatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
