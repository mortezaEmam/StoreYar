<?php

declare(strict_types=1);

namespace Tests\Integration\Modules\Identity\Application\Commands\RotateSession;

use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Application\Commands\RotateSession\RotateSessionCommand;
use StoreYar\Modules\Identity\Application\Commands\RotateSession\RotateSessionHandler;
use StoreYar\Modules\Identity\Application\Results\SessionRotationResult;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent\IdentitySessionModel;
use Tests\TestCase;

final class RotateSessionHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rotates_an_active_session(): void
    {
        $userId = '01JSESSIONUSER000000000001';

        $currentSessionId = SessionId::generate();

        $repository = $this->app->make(SessionRepository::class);

        $repository->create(
            sessionId: $currentSessionId,
            userId: $userId,
            tokenHash: hash('sha256', 'old-token'),
            expiresAt: new \DateTimeImmutable(
                '2026-12-31 12:00:00',
                new \DateTimeZone('UTC'),
            ),
        );

        $handler = $this->app->make(RotateSessionHandler::class);

        $result = $handler->handle(
            new RotateSessionCommand(
                currentSessionId: $currentSessionId,
            ),
        );

        self::assertInstanceOf(
            SessionRotationResult::class,
            $result,
        );

        self::assertNotSame(
            $currentSessionId->value(),
            $result->session->sessionId()->value(),
        );

        self::assertSame(
            $userId,
            $result->session->userId(),
        );

        self::assertSame(
            64,
            strlen($result->token),
        );

        $oldSession = IdentitySessionModel::query()
            ->find($currentSessionId->value());

        $newSession = IdentitySessionModel::query()
            ->find($result->session->sessionId()->value());

        self::assertNotNull($oldSession);
        self::assertNotNull($oldSession->revoked_at);

        self::assertNotNull($newSession);
        self::assertSame($userId, $newSession->user_id);
        self::assertNull($newSession->revoked_at);
        self::assertNotSame(
            $oldSession->token_hash,
            $newSession->token_hash,
        );
        self::assertSame(
            hash('sha256', $result->token),
            $newSession->token_hash,
        );
    }

    public function test_it_rejects_revoked_session(): void
    {
        $userId = '01JSESSIONUSER000000000001';

        $currentSessionId = SessionId::generate();

        $repository = $this->app->make(SessionRepository::class);

        $repository->create(
            sessionId: $currentSessionId,
            userId: $userId,
            tokenHash: hash('sha256', 'revoked-token'),
            expiresAt: new \DateTimeImmutable(
                '2026-12-31 12:00:00',
                new \DateTimeZone('UTC'),
            ),
        );

        $repository->revoke($currentSessionId);

        $handler = $this->app->make(RotateSessionHandler::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid session.');

        $handler->handle(
            new RotateSessionCommand(
                currentSessionId: $currentSessionId,
            ),
        );
    }

    public function test_it_rejects_expired_session(): void
    {
        $userId = '01JSESSIONUSER000000000001';

        $currentSessionId = SessionId::generate();

        $repository = $this->app->make(SessionRepository::class);

        $repository->create(
            sessionId: $currentSessionId,
            userId: $userId,
            tokenHash: hash('sha256', 'expired-token'),
            expiresAt: new \DateTimeImmutable(
                '2020-01-01 00:00:00',
                new \DateTimeZone('UTC'),
            ),
        );

        $handler = $this->app->make(RotateSessionHandler::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid session.');

        $handler->handle(
            new RotateSessionCommand(
                currentSessionId: $currentSessionId,
            ),
        );
    }
}
