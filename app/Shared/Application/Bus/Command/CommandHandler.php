<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Bus\Command;

/**
 * @template TCommand of Command
 * @template TResult
 */
interface CommandHandler
{
    /**
     * @param TCommand $command
     * @return TResult
     */
    public function handle(Command $command): mixed;
}
