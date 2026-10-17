<?php

declare(strict_types=1);

namespace App\Providers;

use Closure;
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
use StoreYar\Shared\Application\Outbox\OutboxEventPublisher;
use StoreYar\Shared\Application\Outbox\OutboxProcessor;
use StoreYar\Shared\Application\Queries\ListAuditLog\ListAuditLogHandler;
use StoreYar\Shared\Application\Queries\ListAuditLog\ListAuditLogQuery;
use StoreYar\Shared\Domain\Contracts\AuditLogger;
use StoreYar\Shared\Domain\Contracts\Clock;
use StoreYar\Shared\Domain\Contracts\Outbox;
use StoreYar\Shared\Infrastructure\Audit\DatabaseAuditLogger;
use StoreYar\Shared\Infrastructure\Clock\SystemClock;
use StoreYar\Shared\Infrastructure\Outbox\EloquentOutbox;
use StoreYar\Shared\Infrastructure\Outbox\LogOutboxEventPublisher;
use StoreYar\Shared\Presentation\Console\PublishOutboxCommand;

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

        $this->app->singleton(Outbox::class, EloquentOutbox::class);
        $this->app->singleton(OutboxEventPublisher::class, LogOutboxEventPublisher::class);
        $this->app->singleton(OutboxProcessor::class);


        $this->app->singleton(AuditLogger::class, DatabaseAuditLogger::class);


        $this->app->afterResolving(
            InMemoryQueryHandlerRegistry::class,
            function (InMemoryQueryHandlerRegistry $registry): void {
                $registry->register(
                    ListAuditLogQuery::class,
                    ListAuditLogHandler::class,
                );
            },
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PublishOutboxCommand::class,
            ]);
        }
    }
}
