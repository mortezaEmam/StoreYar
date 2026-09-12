<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Bus\Query;

final class LaravelQueryBus implements QueryBus
{
    public function __construct(
        private readonly QueryHandlerResolver $resolver,
    ) {
    }

    public function ask(Query $query): mixed
    {
        $handler = $this->resolver->resolve($query);

        return $handler->handle($query);
    }
}
