<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Infrastructure;

use Illuminate\Support\ServiceProvider;
use StoreYar\Modules\Authorization\Application\Commands\ChangeMemberRole\ChangeMemberRoleCommand;
use StoreYar\Modules\Authorization\Application\Commands\ChangeMemberRole\ChangeMemberRoleHandler;
use StoreYar\Modules\Authorization\Application\Commands\RevokeMembership\RevokeMembershipCommand;
use StoreYar\Modules\Authorization\Application\Commands\RevokeMembership\RevokeMembershipHandler;
use StoreYar\Modules\Authorization\Application\Queries\ListMembersByOrganization\ListMembersByOrganizationHandler;
use StoreYar\Modules\Authorization\Application\Queries\ListMembersByOrganization\ListMembersByOrganizationQuery;
use StoreYar\Modules\Organization\Application\Commands\ActivateOrganization\ActivateOrganizationCommand;
use StoreYar\Modules\Organization\Application\Commands\ActivateOrganization\ActivateOrganizationHandler;
use StoreYar\Modules\Organization\Application\Commands\CreateBranch\CreateBranchCommand;
use StoreYar\Modules\Organization\Application\Commands\CreateBranch\CreateBranchHandler;
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
use StoreYar\Modules\Organization\Domain\Contracts\BranchRepository;
use StoreYar\Modules\Organization\Domain\Contracts\OrganizationRepository;
use StoreYar\Modules\Organization\Infrastructure\Persistence\Eloquent\EloquentBranchRepository;
use StoreYar\Modules\Organization\Infrastructure\Persistence\Eloquent\EloquentOrganizationRepository;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry;
use StoreYar\Shared\Application\Bus\Query\QueryHandlerRegistry;
use StoreYar\Modules\Organization\Application\Queries\ListBranchesByOrganization\ListBranchesByOrganizationQuery;
use StoreYar\Modules\Organization\Application\Queries\ListBranchesByOrganization\ListBranchesByOrganizationHandler;

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


        $this->app->singleton(BranchRepository::class, EloquentBranchRepository::class);

        $registry->register(CreateBranchCommand::class, CreateBranchHandler::class);


        $queryRegistry->register(
            ListBranchesByOrganizationQuery::class,
            ListBranchesByOrganizationHandler::class,
        );



        $queryRegistry->register(
            ListMembersByOrganizationQuery::class,
            ListMembersByOrganizationHandler::class,
        );


        $registry->register(ChangeMemberRoleCommand::class, ChangeMemberRoleHandler::class);
        $registry->register(RevokeMembershipCommand::class, RevokeMembershipHandler::class);
    }
}
