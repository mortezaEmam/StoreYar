<?php

declare(strict_types=1);

namespace StoreYar\Modules\Payments\Domain\Events;

use DateTimeImmutable;
use StoreYar\Shared\Domain\Events\DomainEvent;
use Symfony\Component\Uid\Ulid;

final readonly class PaymentRefunded implements DomainEvent
{
    private string $eventId;

    public function __construct(
        private string $paymentId,
        private string $organizationId,
        private string $orderId,
        private string $reason,
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
        return $this->paymentId;
    }

    public function aggregateType(): string
    {
        return 'payments.payment';
    }

    public function paymentId(): string
    {
        return $this->paymentId;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
