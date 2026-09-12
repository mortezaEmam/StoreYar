<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Bus\Query;

/**
 * @template TQuery of Query
 * @template TResult
 */
interface QueryHandler
{
    /**
     * @param TQuery $query
     * @return TResult
     */
    public function handle(Query $query): mixed;
}
