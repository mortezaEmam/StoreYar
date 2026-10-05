<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerMap;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerResolver;
use StoreYar\Shared\Application\Bus\Command\InMemoryCommandHandlerRegistry;
use StoreYar\Shared\Application\Bus\Command\LaravelCommandBus;
use StoreYar\Shared\Application\Bus\Command\LaravelCommandHandlerResolver;
use StoreYar\Shared\Application\Bus\Query\InMemoryQueryHandlerRegistry;
use StoreYar\Shared\Application\Bus\Query\LaravelQueryBus;
use StoreYar\Shared\Application\Bus\Query\LaravelQueryHandlerResolver;
use StoreYar\Shared\Application\Bus\Query\QueryBus;
use StoreYar\Shared\Application\Bus\Query\QueryHandlerMap;
use StoreYar\Shared\Application\Bus\Query\QueryHandlerRegistry;
use StoreYar\Shared\Application\Bus\Query\QueryHandlerResolver;
use StoreYar\Shared\Application\Context\BusinessContext;
use StoreYar\Shared\Application\Context\CurrentBusinessContext;
use StoreYar\Shared\Domain\Contracts\Clock;
use StoreYar\Shared\Infrastructure\Clock\SystemClock;

final class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            CommandHandlerRegistry::class,
            InMemoryCommandHandlerRegistry::class
        );

        $this->app->bind(
            CommandHandlerMap::class,
            fn ($app) => $app
                ->make(CommandHandlerRegistry::class)
                ->map()
        );

        $this->app->singleton(
            CommandHandlerResolver::class,
            LaravelCommandHandlerResolver::class
        );

        $this->app->singleton(
            CommandBus::class,
            LaravelCommandBus::class
        );

        $this->app->singleton(
            QueryHandlerRegistry::class,
            InMemoryQueryHandlerRegistry::class
        );

        $this->app->bind(
            QueryHandlerMap::class,
            fn ($app) => $app
                ->make(QueryHandlerRegistry::class)
                ->map()
        );

        $this->app->singleton(
            QueryHandlerResolver::class,
            LaravelQueryHandlerResolver::class
        );

        $this->app->singleton(
            QueryBus::class,
            LaravelQueryBus::class
        );



        $this->app->singleton(
            Clock::class,
            SystemClock::class,
        );


        $this->app->singleton(CurrentBusinessContext::class);
        $this->app->singleton(
            BusinessContext::class,
            fn ($app) => $app->make(CurrentBusinessContext::class),
        );
    }
}
