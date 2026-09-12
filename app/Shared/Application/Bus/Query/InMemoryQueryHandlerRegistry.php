<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Bus\Query;

use LogicException;

final class InMemoryQueryHandlerRegistry implements QueryHandlerRegistry
{
    /**
     * @var array<class-string<Query>, class-string<QueryHandler>>
     */
    private array $handlers = [];

    public function register(
        string $queryClass,
        string $handlerClass
    ): void {
        if (isset($this->handlers[$queryClass])) {
            throw new LogicException(
                sprintf(
                    'A handler is already registered for query [%s].',
                    $queryClass
                )
            );
        }

        $this->handlers[$queryClass] = $handlerClass;
    }

    public function map(): QueryHandlerMap
    {
        return new InMemoryQueryHandlerMap($this->handlers);
    }
}
