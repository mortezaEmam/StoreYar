<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Infrastructure;

use Illuminate\Support\ServiceProvider;
use StoreYar\Modules\Organization\Application\Commands\CreateOrganization\CreateOrganizationCommand;
use StoreYar\Modules\Organization\Application\Commands\CreateOrganization\CreateOrganizationHandler;
use StoreYar\Modules\Organization\Domain\Contracts\OrganizationRepository;
use StoreYar\Modules\Organization\Infrastructure\Persistence\Eloquent\EloquentOrganizationRepository;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry;

final class OrganizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            OrganizationRepository::class,
            EloquentOrganizationRepository::class,
        );

        $this->app->make(CommandHandlerRegistry::class)->register(
            CreateOrganizationCommand::class,
            CreateOrganizationHandler::class,
        );
    }
}
