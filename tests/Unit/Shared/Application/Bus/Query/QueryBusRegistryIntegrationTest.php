<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Bus\Query;

use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Application\Bus\Query\QueryBus;
use StoreYar\Shared\Application\Bus\Query\QueryHandler;
use StoreYar\Shared\Application\Bus\Query\QueryHandlerRegistry;
use Tests\TestCase;

final class QueryBusRegistryIntegrationTest extends TestCase
{
    public function test_registered_handler_can_be_resolved_and_executed_through_query_bus(): void
    {
        $registry = $this->app->make(
            QueryHandlerRegistry::class
        );

        $registry->register(
            RegisteredQuery::class,
            RegisteredQueryHandler::class
        );

        $result = $this->app
            ->make(QueryBus::class)
            ->ask(
                new RegisteredQuery('product-001')
            );

        $this->assertSame(
            'found:product-001',
            $result
        );
    }
}

final class RegisteredQuery implements Query
{
    public function __construct(
        public readonly string $productId,
    ) {
    }
}

final class RegisteredQueryHandler implements QueryHandler
{
    public function handle(Query $query): mixed
    {
        assert($query instanceof RegisteredQuery);

        return 'found:' . $query->productId;
    }
}
