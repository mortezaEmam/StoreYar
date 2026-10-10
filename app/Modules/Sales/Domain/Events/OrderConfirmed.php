<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Domain\Events;

use DateTimeImmutable;
use StoreYar\Shared\Domain\Events\DomainEvent;
use Symfony\Component\Uid\Ulid;

final readonly class OrderConfirmed implements DomainEvent
{
    private string $eventId;

    /**
     * @param list<array{product_id: string, quantity: int}> $lines
     */
    public function __construct(
        private string $orderId,
        private string $organizationId,
        private array $lines,
        private int $totalAmount,
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

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    /**
     * @return list<array{product_id: string, quantity: int}>
     */
    public function lines(): array
    {
        return $this->lines;
    }

    public function totalAmount(): int
    {
        return $this->totalAmount;
    }

    public function currency(): string
    {
        return $this->currency;
    }
}
