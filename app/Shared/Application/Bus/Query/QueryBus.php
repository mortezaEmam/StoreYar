<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Bus\Query;

interface QueryBus
{
    public function ask(Query $query): mixed;
}
