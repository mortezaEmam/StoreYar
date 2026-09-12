<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Bus\Command;

interface CommandHandlerMap
{
    public function handlerFor(string $commandClass): string;
}
