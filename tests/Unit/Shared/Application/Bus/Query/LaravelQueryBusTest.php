<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Bus\Query;

use LogicException;
use StoreYar\Shared\Application\Bus\Query\InMemoryQueryHandlerMap;
use StoreYar\Shared\Application\Bus\Query\LaravelQueryBus;
use StoreYar\Shared\Application\Bus\Query\LaravelQueryHandlerResolver;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;
use Tests\TestCase;

final class LaravelQueryBusTest extends TestCase
{
    public function test_it_asks_query_to_registered_handler(): void
    {
        $query = new FakeQuery('store-123');

        $this->app->singleton(
            \StoreYar\Shared\Application\Bus\Query\QueryHandlerMap::class,
            fn () => new InMemoryQueryHandlerMap([
                FakeQuery::class => FakeQueryHandler::class,
            ])
        );

        $bus = $this->app->make(LaravelQueryBus::class);

        $result = $bus->ask($query);

        $this->assertSame(
            'result:store-123',
            $result
        );
    }

    public function test_it_rejects_unregistered_query(): void
    {
        $this->expectException(LogicException::class);

        $query = new UnregisteredQuery();

        $bus = $this->app->make(LaravelQueryBus::class);

        $bus->ask($query);
    }
}

final class FakeQuery implements Query
{
    public function __construct(
        public readonly string $value,
    ) {
    }
}

final class UnregisteredQuery implements Query
{
}

final class FakeQueryHandler implements QueryHandler
{
    public function handle(Query $query): mixed
    {
        if (! $query instanceof FakeQuery) {
            throw new LogicException('Unexpected query.');
        }

        return 'result:' . $query->value;
    }
}
