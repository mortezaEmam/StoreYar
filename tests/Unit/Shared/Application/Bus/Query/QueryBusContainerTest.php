<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Bus\Query;

use StoreYar\Shared\Application\Bus\Query\LaravelQueryBus;
use StoreYar\Shared\Application\Bus\Query\LaravelQueryHandlerResolver;
use StoreYar\Shared\Application\Bus\Query\QueryBus;
use StoreYar\Shared\Application\Bus\Query\QueryHandlerMap;
use StoreYar\Shared\Application\Bus\Query\QueryHandlerResolver;
use Tests\TestCase;

final class QueryBusContainerTest extends TestCase
{
    public function test_query_bus_is_resolved_from_container(): void
    {
        $bus = $this->app->make(QueryBus::class);

        $this->assertInstanceOf(
            LaravelQueryBus::class,
            $bus
        );
    }

    public function test_query_handler_resolver_is_resolved_from_container(): void
    {
        $resolver = $this->app->make(
            QueryHandlerResolver::class
        );

        $this->assertInstanceOf(
            LaravelQueryHandlerResolver::class,
            $resolver
        );
    }

    public function test_query_handler_map_is_resolved_from_container(): void
    {
        $map = $this->app->make(
            QueryHandlerMap::class
        );

        $this->assertInstanceOf(
            QueryHandlerMap::class,
            $map
        );
    }
}
