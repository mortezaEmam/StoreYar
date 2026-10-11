<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Domain\Events;

use DateTimeImmutable;
use StoreYar\Shared\Domain\Events\DomainEvent;
use Symfony\Component\Uid\Ulid;

final readonly class StockInitialized implements DomainEvent
{
    private string $eventId;

    public function __construct(
        private string $stockItemId,
        private string $organizationId,
        private string $productId,
        private int $quantity,
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
        return $this->stockItemId;
    }

    public function aggregateType(): string
    {
        return 'inventory.stock_item';
    }

    public function stockItemId(): string
    {
        return $this->stockItemId;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function productId(): string
    {
        return $this->productId;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }
}
