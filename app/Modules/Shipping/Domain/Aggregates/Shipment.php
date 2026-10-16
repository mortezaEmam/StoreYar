<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Domain\Aggregates;

use DateTimeImmutable;
use StoreYar\Modules\Shipping\Domain\Enums\ShipmentStatus;
use StoreYar\Modules\Shipping\Domain\Events\ShipmentCreated;
use StoreYar\Modules\Shipping\Domain\Events\ShipmentDelivered;
use StoreYar\Modules\Shipping\Domain\Events\ShipmentShipped;
use StoreYar\Modules\Shipping\Domain\Exceptions\InvalidShipmentState;
use StoreYar\Modules\Shipping\Domain\ValueObjects\ShipmentId;
use StoreYar\Shared\Domain\Aggregates\AggregateRoot;

final class Shipment extends AggregateRoot
{
    private ShipmentStatus $status;

    private ?string $trackingNumber = null;

    private int $version = 0;

    private function __construct(
        private readonly ShipmentId $shipmentId,
        private readonly string $organizationId,
        private readonly string $orderId,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        ShipmentStatus $status,
    ) {
        parent::__construct($shipmentId->value());
        $this->status = $status;
    }

    public static function create(
        ShipmentId $id,
        string $organizationId,
        string $orderId,
        DateTimeImmutable $now,
    ): self {
        if (trim($organizationId) === '' || trim($orderId) === '') {
            throw new \InvalidArgumentException('Organization and order are required.');
        }

        $shipment = new self(
            shipmentId: $id,
            organizationId: $organizationId,
            orderId: $orderId,
            createdAt: $now,
            updatedAt: $now,
            status: ShipmentStatus::PENDING,
        );

        $shipment->recordEvent(
            new ShipmentCreated(
                shipmentId: $shipment->id(),
                organizationId: $organizationId,
                orderId: $orderId,
                occurredAt: $now,
            ),
        );

        return $shipment;
    }

    public static function reconstitute(
        ShipmentId $id,
        string $organizationId,
        string $orderId,
        ShipmentStatus $status,
        ?string $trackingNumber,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        int $version,
    ): self {
        $shipment = new self($id, $organizationId, $orderId, $createdAt, $updatedAt, $status);
        $shipment->trackingNumber = $trackingNumber;
        $shipment->version = $version;

        return $shipment;
    }

    public function markShipped(string $trackingNumber, DateTimeImmutable $now): void
    {
        if (! $this->status->canShip()) {
            throw new InvalidShipmentState('ship', $this->status);
        }

        if (trim($trackingNumber) === '') {
            throw new \InvalidArgumentException('Tracking number is required.');
        }

        $this->trackingNumber = trim($trackingNumber);
        $this->status = ShipmentStatus::SHIPPED;
        $this->updatedAt = $now;
        $this->version++;

        $this->recordEvent(
            new ShipmentShipped(
                shipmentId: $this->id(),
                organizationId: $this->organizationId,
                orderId: $this->orderId,
                trackingNumber: $this->trackingNumber,
                occurredAt: $now,
            ),
        );
    }

    public function markDelivered(DateTimeImmutable $now): void
    {
        if (! $this->status->canDeliver()) {
            throw new InvalidShipmentState('deliver', $this->status);
        }

        $this->status = ShipmentStatus::DELIVERED;
        $this->updatedAt = $now;
        $this->version++;

        $this->recordEvent(
            new ShipmentDelivered(
                shipmentId: $this->id(),
                organizationId: $this->organizationId,
                orderId: $this->orderId,
                occurredAt: $now,
            ),
        );
    }

    public function shipmentId(): ShipmentId
    {
        return $this->shipmentId;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function status(): ShipmentStatus
    {
        return $this->status;
    }

    public function trackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function version(): int
    {
        return $this->version;
    }
}
