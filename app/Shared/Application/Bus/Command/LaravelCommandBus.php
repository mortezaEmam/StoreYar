<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Bus\Command;

final class LaravelCommandBus implements CommandBus
{
    public function __construct(
        private readonly CommandHandlerResolver $resolver,
    ) {
    }

    public function dispatch(Command $command): mixed
    {
        $handler = $this->resolver->resolve($command);

        return $handler->handle($command);
    }
}
