<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Domain\Events;

use DateTimeImmutable;
use StoreYar\Shared\Domain\Events\DomainEvent;
use Symfony\Component\Uid\Ulid;

final readonly class ShipmentDelivered implements DomainEvent
{
    private string $eventId;

    public function __construct(
        private string $shipmentId,
        private string $organizationId,
        private string $orderId,
        private DateTimeImmutable $occurredAt,
    ) {
        $this->eventId = (string) new Ulid();
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function aggregateId(): string
    {
        return $this->shipmentId;
    }

    public function aggregateType(): string
    {
        return 'shipping.shipment';
    }

    public function shipmentId(): string
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
}
