<?php

declare(strict_types=1);

namespace Tests\Integration\Modules\Identity;

use StoreYar\Modules\Identity\Application\Commands\AuthenticateUser\AuthenticateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\AuthenticateUser\AuthenticateUserHandler;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry;
use Tests\TestCase;
use StoreYar\Modules\Identity\Application\Commands\RevokeSession\RevokeSessionCommand;
use StoreYar\Modules\Identity\Application\Commands\RevokeSession\RevokeSessionHandler;
use StoreYar\Modules\Identity\Application\Commands\RevokeAllUserSessions\RevokeAllUserSessionsCommand;
use StoreYar\Modules\Identity\Application\Commands\RevokeAllUserSessions\RevokeAllUserSessionsHandler;

final class IdentityServiceProviderTest extends TestCase
{
    public function test_authenticate_user_handler_is_registered(): void
    {
        $registry = app(CommandHandlerRegistry::class);

        self::assertSame(
            AuthenticateUserHandler::class,
            $registry
                ->map()
                ->handlerFor(AuthenticateUserCommand::class),
        );
    }

    public function test_session_repository_is_bound_to_eloquent_implementation(): void
    {
        $repository = app(
            \StoreYar\Modules\Identity\Domain\Contracts\SessionRepository::class,
        );

        self::assertInstanceOf(
            \StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent\EloquentSessionRepository::class,
            $repository,
        );
    }

    public function test_revoke_session_handler_is_registered(): void
    {
        $registry = app(CommandHandlerRegistry::class);

        self::assertSame(
            RevokeSessionHandler::class,
            $registry
                ->map()
                ->handlerFor(RevokeSessionCommand::class),
        );
    }

    public function test_session_token_generator_is_bound_to_laravel_implementation(): void
    {
        $generator = app(
            \StoreYar\Modules\Identity\Domain\Contracts\SessionTokenGenerator::class,
        );

        self::assertInstanceOf(
            \StoreYar\Modules\Identity\Infrastructure\Security\LaravelSessionTokenGenerator::class,
            $generator,
        );
    }


    public function test_validate_session_handler_is_registered(): void
    {
        $registry = $this->app->make(
            \StoreYar\Shared\Application\Bus\Query\QueryHandlerRegistry::class,
        );

        self::assertSame(
            \StoreYar\Modules\Identity\Application\Queries\ValidateSession\ValidateSessionHandler::class,
            $registry->map()->handlerFor(
                \StoreYar\Modules\Identity\Application\Queries\ValidateSession\ValidateSessionQuery::class,
            ),
        );
    }


    public function test_revoke_all_user_sessions_handler_is_registered(): void
    {
        $registry = app(CommandHandlerRegistry::class);

        self::assertSame(
            RevokeAllUserSessionsHandler::class,
            $registry
                ->map()
                ->handlerFor(RevokeAllUserSessionsCommand::class),
        );
    }



    public function test_it_registers_rotate_session_command_handler(): void
    {
        $registry = $this->app->make(
            \StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry::class,
        );

        $map = $registry->map();

        self::assertSame(
            \StoreYar\Modules\Identity\Application\Commands\RotateSession\RotateSessionHandler::class,
            $map->handlerFor(
                \StoreYar\Modules\Identity\Application\Commands\RotateSession\RotateSessionCommand::class,
            ),
        );
    }
}
