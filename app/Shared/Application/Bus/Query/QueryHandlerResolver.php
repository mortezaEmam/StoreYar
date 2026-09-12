<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Bus\Query;

interface QueryHandlerResolver
{
    public function resolve(Query $query): QueryHandler;
}
