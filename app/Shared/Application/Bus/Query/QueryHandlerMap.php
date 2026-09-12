<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Bus\Query;

interface QueryHandlerMap
{
    public function handlerFor(string $queryClass): string;
}
