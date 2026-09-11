<?php

declare(strict_types=1);

namespace StoreYar\Shared\Domain\Contracts;

use StoreYar\Shared\Domain\Events\IntegrationEvent;

interface Outbox
{
    public function append(IntegrationEvent $event): void;
}
