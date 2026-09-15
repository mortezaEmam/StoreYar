<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Contracts\Messaging;

interface OutboxStore
{
    public function store(OutboxMessage $message): void;
}
