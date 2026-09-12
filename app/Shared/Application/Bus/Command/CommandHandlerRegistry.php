<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Bus\Command;

interface CommandHandlerRegistry
{
    /**
     * @param class-string<Command> $commandClass
     * @param class-string<CommandHandler> $handlerClass
     */
    public function register(
        string $commandClass,
        string $handlerClass
    ): void;

    public function map(): CommandHandlerMap;
}
