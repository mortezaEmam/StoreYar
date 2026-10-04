<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Infrastructure;

use Illuminate\Support\ServiceProvider;
use StoreYar\Modules\Organization\Application\Commands\ActivateOrganization\ActivateOrganizationCommand;
use StoreYar\Modules\Organization\Application\Commands\ActivateOrganization\ActivateOrganizationHandler;
use StoreYar\Modules\Organization\Application\Commands\CreateOrganization\CreateOrganizationCommand;
use StoreYar\Modules\Organization\Application\Commands\CreateOrganization\CreateOrganizationHandler;
use StoreYar\Modules\Organization\Application\Commands\RenameOrganization\RenameOrganizationCommand;
use StoreYar\Modules\Organization\Application\Commands\RenameOrganization\RenameOrganizationHandler;
use StoreYar\Modules\Organization\Application\Commands\SuspendOrganization\SuspendOrganizationCommand;
use StoreYar\Modules\Organization\Application\Commands\SuspendOrganization\SuspendOrganizationHandler;
use StoreYar\Modules\Organization\Application\Queries\GetOrganizationById\GetOrganizationByIdHandler;
use StoreYar\Modules\Organization\Application\Queries\GetOrganizationById\GetOrganizationByIdQuery;
use StoreYar\Modules\Organization\Application\Queries\ListOrganizationsByOwner\ListOrganizationsByOwnerHandler;
use StoreYar\Modules\Organization\Application\Queries\ListOrganizationsByOwner\ListOrganizationsByOwnerQuery;
use StoreYar\Modules\Organization\Domain\Contracts\OrganizationRepository;
use StoreYar\Modules\Organization\Infrastructure\Persistence\Eloquent\EloquentOrganizationRepository;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry;
use StoreYar\Shared\Application\Bus\Query\QueryHandlerRegistry;

final class OrganizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            OrganizationRepository::class,
            EloquentOrganizationRepository::class,
        );

        $registry = $this->app->make(CommandHandlerRegistry::class);

        $registry->register(
            CreateOrganizationCommand::class,
            CreateOrganizationHandler::class,
        );

        $registry->register(
            RenameOrganizationCommand::class,
            RenameOrganizationHandler::class,
        );

        $registry->register(
            SuspendOrganizationCommand::class,
            SuspendOrganizationHandler::class,
        );

        $registry->register(
            ActivateOrganizationCommand::class,
            ActivateOrganizationHandler::class,
        );

        $queryRegistry = $this->app->make(QueryHandlerRegistry::class);

        $queryRegistry->register(
            GetOrganizationByIdQuery::class,
            GetOrganizationByIdHandler::class,
        );

        $queryRegistry->register(
            ListOrganizationsByOwnerQuery::class,
            ListOrganizationsByOwnerHandler::class,
        );
    }
}
