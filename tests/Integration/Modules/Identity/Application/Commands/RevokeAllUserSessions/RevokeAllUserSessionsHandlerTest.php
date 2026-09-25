<?php

declare(strict_types=1);

namespace Tests\Integration\Modules\Identity\Application\Commands\RevokeAllUserSessions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Identity\Application\Commands\RevokeAllUserSessions\RevokeAllUserSessionsCommand;
use StoreYar\Modules\Identity\Application\Commands\RevokeAllUserSessions\RevokeAllUserSessionsHandler;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use Tests\TestCase;

final class RevokeAllUserSessionsHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_revokes_all_active_sessions_for_user(): void
    {
        $targetUserId = '01JSESSIONUSER000000000001';
        $otherUserId = '01JSESSIONUSER000000000002';

        $firstSessionId = SessionId::generate();
        $secondSessionId = SessionId::generate();
        $otherSessionId = SessionId::generate();

        $repository = $this->app->make(SessionRepository::class);

        $repository->create(
            sessionId: $firstSessionId,
            userId: $targetUserId,
            tokenHash: hash('sha256', 'first-session-token'),
            expiresAt: new \DateTimeImmutable(
                '2026-12-31 12:00:00',
                new \DateTimeZone('UTC'),
            ),
        );

        $repository->create(
            sessionId: $secondSessionId,
            userId: $targetUserId,
            tokenHash: hash('sha256', 'second-session-token'),
            expiresAt: new \DateTimeImmutable(
                '2026-12-31 12:00:00',
                new \DateTimeZone('UTC'),
            ),
        );

        $repository->create(
            sessionId: $otherSessionId,
            userId: $otherUserId,
            tokenHash: hash('sha256', 'other-session-token'),
            expiresAt: new \DateTimeImmutable(
                '2026-12-31 12:00:00',
                new \DateTimeZone('UTC'),
            ),
        );

        $handler = $this->app->make(
            RevokeAllUserSessionsHandler::class,
        );

        $result = $handler->handle(
            new RevokeAllUserSessionsCommand(
                userId: $targetUserId,
            ),
        );

        self::assertNull($result);

        $firstSession = DB::table('identity_sessions')
            ->where('id', $firstSessionId->value())
            ->first();

        $secondSession = DB::table('identity_sessions')
            ->where('id', $secondSessionId->value())
            ->first();

        $otherSession = DB::table('identity_sessions')
            ->where('id', $otherSessionId->value())
            ->first();

        self::assertNotNull($firstSession);
        self::assertNotNull($secondSession);
        self::assertNotNull($otherSession);

        self::assertNotNull($firstSession->revoked_at);
        self::assertNotNull($secondSession->revoked_at);
        self::assertNull($otherSession->revoked_at);
    }
}
