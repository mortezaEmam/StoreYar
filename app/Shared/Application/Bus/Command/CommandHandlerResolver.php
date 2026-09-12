<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Bus\Command;

interface CommandHandlerResolver
{
    public function resolve(Command $command): CommandHandler;
}
