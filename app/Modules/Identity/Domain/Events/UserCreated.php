<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Domain\Events;

use DateTimeImmutable;
use Symfony\Component\Uid\Ulid;
use StoreYar\Shared\Domain\Events\DomainEvent;

final readonly class UserCreated implements DomainEvent
{
    private string $eventId;

    public function __construct(
        private string $userId,
        private string $email,
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
        return $this->userId;
    }

    public function aggregateType(): string
    {
        return 'identity.user';
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function email(): string
    {
        return $this->email;
    }
}
