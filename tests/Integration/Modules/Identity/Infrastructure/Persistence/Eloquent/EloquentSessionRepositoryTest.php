<?php

declare(strict_types=1);

namespace Tests\Integration\Modules\Identity\Infrastructure\Persistence\Eloquent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent\IdentitySessionModel;
use Tests\TestCase;

final class EloquentSessionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_revokes_all_sessions_for_a_user(): void
    {
        $userId = '01JSESSIONUSER000000000001';

        $firstSessionId = SessionId::generate();
        $secondSessionId = SessionId::generate();
        $otherUserSessionId = SessionId::generate();

        $repository = $this->app->make(SessionRepository::class);

        $repository->create(
            sessionId: $firstSessionId,
            userId: $userId,
            tokenHash: hash('sha256', 'first-token'),
            expiresAt: new \DateTimeImmutable(
                '2026-12-31 12:00:00',
                new \DateTimeZone('UTC'),
            ),
        );

        $repository->create(
            sessionId: $secondSessionId,
            userId: $userId,
            tokenHash: hash('sha256', 'second-token'),
            expiresAt: new \DateTimeImmutable(
                '2026-12-31 12:00:00',
                new \DateTimeZone('UTC'),
            ),
        );

        $repository->create(
            sessionId: $otherUserSessionId,
            userId: '01JSESSIONUSER000000000002',
            tokenHash: hash('sha256', 'other-user-token'),
            expiresAt: new \DateTimeImmutable(
                '2026-12-31 12:00:00',
                new \DateTimeZone('UTC'),
            ),
        );

        $repository->revokeAllForUser($userId);

        $first = DB::table('identity_sessions')
            ->where('id', $firstSessionId->value())
            ->first();

        $second = DB::table('identity_sessions')
            ->where('id', $secondSessionId->value())
            ->first();

        $otherUser = DB::table('identity_sessions')
            ->where('id', $otherUserSessionId->value())
            ->first();

        self::assertNotNull($first);
        self::assertNotNull($first->revoked_at);

        self::assertNotNull($second);
        self::assertNotNull($second->revoked_at);

        self::assertNotNull($otherUser);
        self::assertNull($otherUser->revoked_at);
    }


    public function test_find_by_id_returns_session(): void
    {
        $sessionId = SessionId::generate();
        $userId = '01JSESSIONUSER000000000001';
        $tokenHash = hash('sha256', 'find-by-id-token');

        $expiresAt = new \DateTimeImmutable('+30 days');

        IdentitySessionModel::query()->create([
            'id' => $sessionId->value(),
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'revoked_at' => null,
        ]);

        $repository = $this->app->make(SessionRepository::class);

        $session = $repository->findById($sessionId);

        self::assertNotNull($session);
        self::assertTrue($session->sessionId()->equals($sessionId));
        self::assertSame($userId, $session->userId());
        self::assertSame($tokenHash, $session->tokenHash());
        self::assertSame(
            $expiresAt->format('Y-m-d H:i:s'),
            $session->expiresAt()->format('Y-m-d H:i:s'),
        );
        self::assertFalse($session->isRevoked());
    }
}
