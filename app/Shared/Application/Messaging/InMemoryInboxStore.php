<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Messaging;

use StoreYar\Shared\Application\Contracts\Messaging\InboxMessage;
use StoreYar\Shared\Application\Contracts\Messaging\InboxStore;

final class InMemoryInboxStore implements InboxStore
{
    /**
     * @var array<string, InboxMessage>
     */
    private array $messages = [];

    public function hasProcessed(string $eventId): bool
    {
        return isset($this->messages[$eventId]);
    }

    public function markProcessed(InboxMessage $message): void
    {
        $this->messages[$message->eventId()] = $message;
    }

    /**
     * @return list<InboxMessage>
     */
    public function messages(): array
    {
        return array_values($this->messages);
    }
}
