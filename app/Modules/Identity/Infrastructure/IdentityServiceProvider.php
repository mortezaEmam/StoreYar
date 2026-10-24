<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Infrastructure;

use Illuminate\Support\ServiceProvider;
use StoreYar\Modules\Identity\Application\Commands\AuthenticateUser\AuthenticateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\AuthenticateUser\AuthenticateUserHandler;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserHandler;
use StoreYar\Modules\Identity\Application\Commands\RequestPasswordReset\RequestPasswordResetCommand;
use StoreYar\Modules\Identity\Application\Commands\RequestPasswordReset\RequestPasswordResetHandler;
use StoreYar\Modules\Identity\Application\Commands\ResetPassword\ResetPasswordCommand;
use StoreYar\Modules\Identity\Application\Commands\ResetPassword\ResetPasswordHandler;
use StoreYar\Modules\Identity\Application\Commands\RevokeAllUserSessions\RevokeAllUserSessionsCommand;
use StoreYar\Modules\Identity\Application\Commands\RevokeAllUserSessions\RevokeAllUserSessionsHandler;
use StoreYar\Modules\Identity\Application\Commands\RevokeSession\RevokeSessionCommand;
use StoreYar\Modules\Identity\Application\Commands\RevokeSession\RevokeSessionHandler;
use StoreYar\Modules\Identity\Application\Commands\RotateSession\RotateSessionCommand;
use StoreYar\Modules\Identity\Application\Commands\RotateSession\RotateSessionHandler;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordHandler;
use StoreYar\Modules\Identity\Application\Queries\GetUserById\GetUserByIdHandler;
use StoreYar\Modules\Identity\Application\Queries\GetUserById\GetUserByIdQuery;
use StoreYar\Modules\Identity\Application\Queries\ValidateSession\ValidateSessionHandler;
use StoreYar\Modules\Identity\Application\Queries\ValidateSession\ValidateSessionQuery;
use StoreYar\Modules\Identity\Application\Security\PasswordResetNotifier;
use StoreYar\Modules\Identity\Domain\Contracts\PasswordHasher;
use StoreYar\Modules\Identity\Domain\Contracts\PasswordResetTokenRepository;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\Contracts\SessionTokenGenerator;
use StoreYar\Modules\Identity\Domain\Contracts\UserCredentialRepository;
use StoreYar\Modules\Identity\Domain\Contracts\UserRepository;
use StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent\EloquentPasswordResetTokenRepository;
use StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent\EloquentSessionRepository;
use StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent\EloquentUserCredentialRepository;
use StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent\EloquentUserRepository;
use StoreYar\Modules\Identity\Infrastructure\Security\ArrayPasswordResetNotifier;
use StoreYar\Modules\Identity\Infrastructure\Security\LaravelPasswordHasher;
use StoreYar\Modules\Identity\Infrastructure\Security\LaravelSessionTokenGenerator;
use StoreYar\Modules\Identity\Infrastructure\Security\MailPasswordResetNotifier;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry;
use StoreYar\Shared\Application\Bus\Query\QueryHandlerRegistry;

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

        $this->app->singleton(
            UserCredentialRepository::class,
            EloquentUserCredentialRepository::class,
        );

        $this->app->singleton(
            SessionRepository::class,
            EloquentSessionRepository::class,
        );

        $this->app->singleton(
            SessionTokenGenerator::class,
            LaravelSessionTokenGenerator::class,
        );

        $this->app->singleton(
            PasswordResetTokenRepository::class,
            EloquentPasswordResetTokenRepository::class,
        );

        $this->app->singleton(PasswordResetNotifier::class, function ($app) {
            if ($app->environment('testing')) {
                return new ArrayPasswordResetNotifier();
            }

            return new MailPasswordResetNotifier();
        });

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

        $registry = $this->app->make(CommandHandlerRegistry::class);

        $registry->register(AuthenticateUserCommand::class, AuthenticateUserHandler::class);
        $registry->register(RevokeSessionCommand::class, RevokeSessionHandler::class);
        $registry->register(RevokeAllUserSessionsCommand::class, RevokeAllUserSessionsHandler::class);
        $registry->register(RotateSessionCommand::class, RotateSessionHandler::class);
        $registry->register(SetPasswordCommand::class, SetPasswordHandler::class);
        $registry->register(RequestPasswordResetCommand::class, RequestPasswordResetHandler::class);
        $registry->register(ResetPasswordCommand::class, ResetPasswordHandler::class);

        $this->app->make(QueryHandlerRegistry::class)->register(
            ValidateSessionQuery::class,
            ValidateSessionHandler::class,
        );
    }
}
