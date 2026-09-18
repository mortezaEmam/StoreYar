<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Identity\Infrastructure;

use StoreYar\Modules\Identity\Application\Queries\GetUserById\GetUserByIdHandler;
use StoreYar\Modules\Identity\Application\Queries\GetUserById\GetUserByIdQuery;
use StoreYar\Shared\Application\Bus\Query\QueryHandlerMap;
use StoreYar\Shared\Application\Bus\Query\QueryHandlerRegistry;
use Tests\TestCase;

final class GetUserByIdQueryBusTest extends TestCase
{
    public function test_get_user_by_id_query_is_registered_with_its_handler(): void
    {
        $map = $this->app->make(QueryHandlerMap::class);

        self::assertSame(
            GetUserByIdHandler::class,
            $map->handlerFor(GetUserByIdQuery::class),
        );
    }

    public function test_get_user_by_id_handler_can_be_resolved_from_the_container(): void
    {
        $registry = $this->app->make(QueryHandlerRegistry::class);

        $map = $registry->map();

        $handlerClass = $map->handlerFor(GetUserByIdQuery::class);
        $handler = $this->app->make($handlerClass);

        self::assertInstanceOf(
            GetUserByIdHandler::class,
            $handler,
        );
    }
}
