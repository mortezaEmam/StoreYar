<?php

declare(strict_types=1);

namespace Tests\Integration\Modules\Identity\Application\Queries\ValidateSession;

use DateTimeImmutable;
use DateTimeZone;
use StoreYar\Modules\Identity\Application\Queries\ValidateSession\ValidateSessionHandler;
use StoreYar\Modules\Identity\Application\Queries\ValidateSession\ValidateSessionQuery;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\Contracts\SessionTokenGenerator;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class ValidateSessionHandlerTest extends TestCase
{
    use RefreshDatabase;
    private const USER_ID = '01JSESSIONUSER000000000001';

    public function test_it_returns_active_session_id(): void
    {
        $token = 'integration-active-session-token';

        $sessionId = SessionId::generate();

        $sessions = $this->app->make(SessionRepository::class);
        $tokenGenerator = $this->app->make(SessionTokenGenerator::class);

        $sessions->create(
            sessionId: $sessionId,
            userId: self::USER_ID,
            tokenHash: $tokenGenerator->hash($token),
            expiresAt: new DateTimeImmutable(
                '+30 days',
                new DateTimeZone('UTC'),
            ),
        );

        $handler = $this->app->make(ValidateSessionHandler::class);

        $result = $handler->handle(
            new ValidateSessionQuery(
                token: $token,
            ),
        );

        self::assertNotNull($result);
        self::assertTrue($sessionId->equals($result));
    }

    public function test_it_returns_null_for_revoked_session(): void
    {
        $token = 'integration-revoked-session-token';

        $sessionId = SessionId::generate();

        $sessions = $this->app->make(SessionRepository::class);
        $tokenGenerator = $this->app->make(SessionTokenGenerator::class);

        $sessions->create(
            sessionId: $sessionId,
            userId: self::USER_ID,
            tokenHash: $tokenGenerator->hash($token),
            expiresAt: new DateTimeImmutable(
                '+30 days',
                new DateTimeZone('UTC'),
            ),
        );

        $sessions->revoke($sessionId);

        $handler = $this->app->make(ValidateSessionHandler::class);

        $result = $handler->handle(
            new ValidateSessionQuery(
                token: $token,
            ),
        );

        self::assertNull($result);
    }

    public function test_it_returns_null_for_expired_session(): void
    {
        $token = 'integration-expired-session-token';

        $sessionId = SessionId::generate();

        $sessions = $this->app->make(SessionRepository::class);
        $tokenGenerator = $this->app->make(SessionTokenGenerator::class);

        $sessions->create(
            sessionId: $sessionId,
            userId: self::USER_ID,
            tokenHash: $tokenGenerator->hash($token),
            expiresAt: new DateTimeImmutable(
                '2020-01-01 12:00:00',
                new DateTimeZone('UTC'),
            ),
        );

        $handler = $this->app->make(ValidateSessionHandler::class);

        $result = $handler->handle(
            new ValidateSessionQuery(
                token: $token,
            ),
        );

        self::assertNull($result);
    }

    public function test_it_validates_using_hashed_token(): void
    {
        $token = 'integration-hashed-token';

        $sessionId = SessionId::generate();

        $sessions = $this->app->make(SessionRepository::class);
        $tokenGenerator = $this->app->make(SessionTokenGenerator::class);

        $tokenHash = $tokenGenerator->hash($token);

        $sessions->create(
            sessionId: $sessionId,
            userId: self::USER_ID,
            tokenHash: $tokenHash,
            expiresAt: new DateTimeImmutable(
                '+30 days',
                new DateTimeZone('UTC'),
            ),
        );

        self::assertNotSame($token, $tokenHash);
        self::assertSame(
            hash('sha256', $token),
            $tokenHash,
        );

        $handler = $this->app->make(ValidateSessionHandler::class);

        $result = $handler->handle(
            new ValidateSessionQuery(
                token: $token,
            ),
        );

        self::assertNotNull($result);
        self::assertTrue($sessionId->equals($result));
    }
}
