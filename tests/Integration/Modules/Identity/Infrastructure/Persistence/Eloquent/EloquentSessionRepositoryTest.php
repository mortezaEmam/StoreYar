<?php

declare(strict_types=1);

namespace Tests\Integration\Modules\Identity\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent\EloquentSessionRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use Tests\TestCase;

final class EloquentSessionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_and_finds_an_active_session(): void
    {
        $repository = new EloquentSessionRepository();

        $sessionId = SessionId::generate();

        $repository->create(
            sessionId: $sessionId,
            userId: '01JSESSIONUSER000000000001',
            tokenHash: 'token-hash-active',
            expiresAt: new DateTimeImmutable(
                '2026-12-01 10:00:00',
                new DateTimeZone('UTC'),
            ),
        );

        $found = $repository->findActiveByTokenHash(
            tokenHash: 'token-hash-active',
            now: new DateTimeImmutable(
                '2026-12-01 09:00:00',
                new DateTimeZone('UTC'),
            ),
        );

        self::assertNotNull($found);
        self::assertTrue($sessionId->equals($found));
    }

    public function test_it_does_not_find_an_expired_session(): void
    {
        $repository = new EloquentSessionRepository();

        $sessionId = SessionId::generate();

        $repository->create(
            sessionId: $sessionId,
            userId: '01JSESSIONUSER000000000001',
            tokenHash: 'token-hash-expired',
            expiresAt: new DateTimeImmutable(
                '2026-12-01 10:00:00',
                new DateTimeZone('UTC'),
            ),
        );

        $found = $repository->findActiveByTokenHash(
            tokenHash: 'token-hash-expired',
            now: new DateTimeImmutable(
                '2026-12-01 10:00:01',
                new DateTimeZone('UTC'),
            ),
        );

        self::assertNull($found);
    }

    public function test_it_does_not_find_a_revoked_session(): void
    {
        $repository = new EloquentSessionRepository();

        $sessionId = SessionId::generate();

        $repository->create(
            sessionId: $sessionId,
            userId: '01JSESSIONUSER000000000001',
            tokenHash: 'token-hash-revoked',
            expiresAt: new DateTimeImmutable(
                '2026-12-01 10:00:00',
                new DateTimeZone('UTC'),
            ),
        );

        $repository->revoke($sessionId);

        $found = $repository->findActiveByTokenHash(
            tokenHash: 'token-hash-revoked',
            now: new DateTimeImmutable(
                '2026-12-01 09:00:00',
                new DateTimeZone('UTC'),
            ),
        );

        self::assertNull($found);
    }
}
