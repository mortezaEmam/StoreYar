<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Bus\Query;

interface QueryHandlerRegistry
{
    /**
     * @param class-string<Query> $queryClass
     * @param class-string<QueryHandler> $handlerClass
     */
    public function register(
        string $queryClass,
        string $handlerClass
    ): void;

    public function map(): QueryHandlerMap;
}
