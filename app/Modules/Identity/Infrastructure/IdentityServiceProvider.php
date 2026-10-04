<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Infrastructure;

use Illuminate\Support\ServiceProvider;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordHandler;
use StoreYar\Modules\Identity\Domain\Contracts\PasswordHasher;
use StoreYar\Modules\Identity\Domain\Contracts\UserRepository;
use StoreYar\Modules\Identity\Infrastructure\Security\LaravelPasswordHasher;
use StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent\EloquentUserRepository;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserHandler;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry;
use StoreYar\Modules\Identity\Application\Queries\GetUserById\GetUserByIdHandler;
use StoreYar\Modules\Identity\Application\Queries\GetUserById\GetUserByIdQuery;
use StoreYar\Shared\Application\Bus\Query\QueryHandlerRegistry;
use StoreYar\Modules\Identity\Domain\Contracts\UserCredentialRepository;
use StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent\EloquentUserCredentialRepository;
use StoreYar\Modules\Identity\Application\Commands\AuthenticateUser\AuthenticateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\AuthenticateUser\AuthenticateUserHandler;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent\EloquentSessionRepository;
use StoreYar\Modules\Identity\Domain\Contracts\SessionTokenGenerator;
use StoreYar\Modules\Identity\Infrastructure\Security\LaravelSessionTokenGenerator;
use StoreYar\Modules\Identity\Application\Commands\RevokeSession\RevokeSessionCommand;
use StoreYar\Modules\Identity\Application\Commands\RevokeSession\RevokeSessionHandler;
use StoreYar\Modules\Identity\Application\Queries\ValidateSession\ValidateSessionHandler;
use StoreYar\Modules\Identity\Application\Queries\ValidateSession\ValidateSessionQuery;
use StoreYar\Modules\Identity\Application\Commands\RevokeAllUserSessions\RevokeAllUserSessionsCommand;
use StoreYar\Modules\Identity\Application\Commands\RevokeAllUserSessions\RevokeAllUserSessionsHandler;
use StoreYar\Modules\Identity\Application\Commands\RotateSession\RotateSessionCommand;
use StoreYar\Modules\Identity\Application\Commands\RotateSession\RotateSessionHandler;
final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            PasswordHasher::class,
            LaravelPasswordHasher::class,
        );


        $this->app->singleton(
            UserRepository::class,
            EloquentUserRepository::class,
        );


        $this->app->afterResolving(
            CommandHandlerRegistry::class,
            static function (CommandHandlerRegistry $registry): void {
                $registry->register(
                    CreateUserCommand::class,
                    CreateUserHandler::class,
                );
            },
        );

        $this->app->afterResolving(
            QueryHandlerRegistry::class,
            static function (QueryHandlerRegistry $registry): void {
                $registry->register(
                    GetUserByIdQuery::class,
                    GetUserByIdHandler::class,
                );
            },
        );


        $this->app->singleton(
            UserCredentialRepository::class,
            EloquentUserCredentialRepository::class,
        );


        $this->app->make(CommandHandlerRegistry::class)->register(
            AuthenticateUserCommand::class,
            AuthenticateUserHandler::class,
        );

        $this->app->make(CommandHandlerRegistry::class)->register(
            RevokeSessionCommand::class,
            RevokeSessionHandler::class,
        );


        $this->app->singleton(
            SessionRepository::class,
            EloquentSessionRepository::class,
        );


        $this->app->singleton(
            SessionTokenGenerator::class,
            LaravelSessionTokenGenerator::class,
        );


        $this->app->make(QueryHandlerRegistry::class)->register(
            ValidateSessionQuery::class,
            ValidateSessionHandler::class,
        );


        $this->app->make(CommandHandlerRegistry::class)->register(
            RevokeAllUserSessionsCommand::class,
            RevokeAllUserSessionsHandler::class,
        );


        $this->app->make(CommandHandlerRegistry::class)->register(
            RotateSessionCommand::class,
            RotateSessionHandler::class,
        );


        $this->app->make(CommandHandlerRegistry::class)->register(
            SetPasswordCommand::class,
            SetPasswordHandler::class,
        );
    }
}
