<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Bus\Query;

use LogicException;

final class InMemoryQueryHandlerMap implements QueryHandlerMap
{
    /**
     * @param array<class-string<Query>, class-string<QueryHandler>> $handlers
     */
    public function __construct(
        private readonly array $handlers,
    ) {
    }

    public function handlerFor(string $queryClass): string
    {
        $handler = $this->handlers[$queryClass] ?? null;

        if ($handler === null) {
            throw new LogicException(
                sprintf(
                    'No handler registered for query [%s].',
                    $queryClass
                )
            );
        }

        return $handler;
    }
}
