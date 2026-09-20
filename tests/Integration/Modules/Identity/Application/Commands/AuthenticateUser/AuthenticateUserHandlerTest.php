<?php

declare(strict_types=1);

namespace Tests\Integration\Modules\Identity\Application\Commands\AuthenticateUser;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Commands\AuthenticateUser\AuthenticateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\AuthenticateUser\AuthenticateUserHandler;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserHandler;
use StoreYar\Modules\Identity\Application\Results\AuthenticationResult;
use StoreYar\Modules\Identity\Domain\Contracts\UserCredentialRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;
use Tests\TestCase;

final class AuthenticateUserHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_authenticates_user_and_persists_session(): void
    {
        $createUserHandler = $this->app->make(CreateUserHandler::class);

        $user = $createUserHandler->handle(
            new CreateUserCommand(
                email: 'auth@example.com',
                name: 'Auth User',
            ),
        );

        $credentials = $this->app->make(UserCredentialRepository::class);

        $credentials->savePasswordHash(
            UserId::fromString($user->id()),
            password_hash('plain-secret', PASSWORD_BCRYPT),
        );

        $handler = $this->app->make(AuthenticateUserHandler::class);

        $result = $handler->handle(
            new AuthenticateUserCommand(
                email: 'auth@example.com',
                password: 'plain-secret',
            ),
        );

        self::assertInstanceOf(
            AuthenticationResult::class,
            $result,
        );

        self::assertSame(
            $user->id(),
            $result->user->id(),
        );

        self::assertNotSame(
            '',
            $result->token,
        );

        self::assertSame(
            $user->id(),
            $result->session->userId(),
        );

        self::assertTrue(
            $result->session->isActive(
                new \DateTimeImmutable('2026-01-01 12:00:00', new \DateTimeZone('UTC')),
            ),
        );

        self::assertDatabaseHas('identity_sessions', [
            'id' => $result->session->sessionId()->value(),
            'user_id' => $user->id(),
            'token_hash' => hash('sha256', $result->token),
            'revoked_at' => null,
        ]);

        $this->assertDatabaseMissing('identity_sessions', [
            'token_hash' => $result->token,
        ]);
    }
}
