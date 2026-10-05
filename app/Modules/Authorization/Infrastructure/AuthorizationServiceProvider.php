<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Infrastructure;

use Illuminate\Support\ServiceProvider;
use StoreYar\Modules\Authorization\Application\Commands\GrantMembership\GrantMembershipCommand;
use StoreYar\Modules\Authorization\Application\Commands\GrantMembership\GrantMembershipHandler;
use StoreYar\Modules\Authorization\Application\Queries\GetMembership\GetMembershipHandler;
use StoreYar\Modules\Authorization\Application\Queries\GetMembership\GetMembershipQuery;
use StoreYar\Modules\Authorization\Domain\Contracts\MembershipRepository;
use StoreYar\Modules\Authorization\Infrastructure\Persistence\Eloquent\EloquentMembershipRepository;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry;
use StoreYar\Shared\Application\Bus\Query\QueryHandlerRegistry;

final class AuthorizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            MembershipRepository::class,
            EloquentMembershipRepository::class,
        );

        $this->app->make(CommandHandlerRegistry::class)->register(
            GrantMembershipCommand::class,
            GrantMembershipHandler::class,
        );

        $queryRegistry = $this->app->make(QueryHandlerRegistry::class);
        $queryRegistry->register(
            GetMembershipQuery::class,
            GetMembershipHandler::class,
        );
    }
}
