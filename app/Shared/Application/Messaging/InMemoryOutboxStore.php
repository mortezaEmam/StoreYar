<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Messaging;

use StoreYar\Shared\Application\Contracts\Messaging\OutboxMessage;
use StoreYar\Shared\Application\Contracts\Messaging\OutboxStore;

final class InMemoryOutboxStore implements OutboxStore
{
    /**
     * @var list<OutboxMessage>
     */
    private array $messages = [];

    public function store(OutboxMessage $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * @return list<OutboxMessage>
     */
    public function messages(): array
    {
        return $this->messages;
    }
}
