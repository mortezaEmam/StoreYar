<?php

declare(strict_types=1);

namespace StoreYar\Shared\Domain\Contracts;

use StoreYar\Shared\Domain\Events\DomainEvent;

interface Outbox
{
    public function record(DomainEvent $event): void;

    /**
     * @param list<DomainEvent> $events
     */
    public function recordMany(array $events): void;
}
