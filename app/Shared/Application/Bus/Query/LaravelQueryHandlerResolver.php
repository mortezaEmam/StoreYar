<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Bus\Query;

use Illuminate\Contracts\Container\Container;
use LogicException;

final class LaravelQueryHandlerResolver implements QueryHandlerResolver
{
    public function __construct(
        private readonly Container $container,
    ) {
    }

    public function resolve(Query $query): QueryHandler
    {
        $queryClass = $query::class;

        $handlerClass = $this->container->get(
            QueryHandlerMap::class
        )->handlerFor($queryClass);

        $handler = $this->container->make($handlerClass);

        if (! $handler instanceof QueryHandler) {
            throw new LogicException(
                sprintf(
                    'Query handler [%s] must implement [%s].',
                    $handlerClass,
                    QueryHandler::class
                )
            );
        }

        return $handler;
    }
}
