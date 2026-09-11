<?php

declare(strict_types=1);

namespace StoreYar\Shared\Domain\Aggregates;

use StoreYar\Shared\Domain\Entities\Entity;
use StoreYar\Shared\Domain\Events\DomainEvent;

abstract class AggregateRoot extends Entity
{
    /**
     * @var list<DomainEvent>
     */
    private array $domainEvents = [];

    protected function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return list<DomainEvent>
     */
    public function domainEvents(): array
    {
        return $this->domainEvents;
    }

    /**
     * @return list<DomainEvent>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;

        $this->domainEvents = [];

        return $events;
    }
}
