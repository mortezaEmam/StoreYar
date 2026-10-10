<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Domain\Events;

use DateTimeImmutable;
use StoreYar\Shared\Domain\Events\DomainEvent;
use Symfony\Component\Uid\Ulid;

final readonly class OrderLineAdded implements DomainEvent
{
    private string $eventId;

    public function __construct(
        private string $orderId,
        private string $productId,
        private int $quantity,
        private int $unitPriceAmount,
        private string $currency,
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
        return $this->orderId;
    }

    public function aggregateType(): string
    {
        return 'sales.order';
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function productId(): string
    {
        return $this->productId;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function unitPriceAmount(): int
    {
        return $this->unitPriceAmount;
    }

    public function currency(): string
    {
        return $this->currency;
    }
}
