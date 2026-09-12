<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Bus\Query;

use LogicException;
use StoreYar\Shared\Application\Bus\Query\InMemoryQueryHandlerRegistry;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;
use Tests\TestCase;

final class QueryHandlerRegistryTest extends TestCase
{
    public function test_it_registers_query_handler(): void
    {
        $registry = new InMemoryQueryHandlerRegistry();

        $registry->register(
            FakeRegisteredQuery::class,
            FakeRegisteredQueryHandler::class
        );

        $map = $registry->map();

        $this->assertSame(
            FakeRegisteredQueryHandler::class,
            $map->handlerFor(FakeRegisteredQuery::class)
        );
    }

    public function test_it_rejects_duplicate_query_handler_registration(): void
    {
        $registry = new InMemoryQueryHandlerRegistry();

        $registry->register(
            FakeRegisteredQuery::class,
            FakeRegisteredQueryHandler::class
        );

        $this->expectException(LogicException::class);

        $registry->register(
            FakeRegisteredQuery::class,
            AnotherFakeQueryHandler::class
        );
    }
}

final class FakeRegisteredQuery implements Query
{
}

final class FakeRegisteredQueryHandler implements QueryHandler
{
    public function handle(Query $query): mixed
    {
        return null;
    }
}

final class AnotherFakeQueryHandler implements QueryHandler
{
    public function handle(Query $query): mixed
    {
        return null;
    }
}
