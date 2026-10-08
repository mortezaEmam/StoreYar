<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Domain\Events;

use DateTimeImmutable;
use StoreYar\Shared\Domain\Events\DomainEvent;
use Symfony\Component\Uid\Ulid;

final readonly class ProductCreated implements DomainEvent
{
    private string $eventId;

    public function __construct(
        private string $productId,
        private string $organizationId,
        private string $name,
        private string $sku,
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
        return $this->productId;
    }

    public function aggregateType(): string
    {
        return 'catalog.product';
    }

    public function productId(): string
    {
        return $this->productId;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function sku(): string
    {
        return $this->sku;
    }
}
