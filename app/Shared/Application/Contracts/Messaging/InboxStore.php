<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Contracts\Messaging;

interface InboxStore
{
    public function hasProcessed(string $eventId): bool;

    public function markProcessed(InboxMessage $message): void;
}
