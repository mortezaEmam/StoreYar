<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Domain\Entities;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Identity\Domain\Entities\Session;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;

final class SessionTest extends TestCase
{
    public function test_it_creates_an_active_session(): void
    {
        $now = new DateTimeImmutable(
            '2026-01-01 10:00:00',
            new DateTimeZone('UTC'),
        );

        $expiresAt = new DateTimeImmutable(
            '2026-01-01 11:00:00',
            new DateTimeZone('UTC'),
        );

        $session = Session::create(
            sessionId: SessionId::generate(),
            userId: '01JSESSIONUSER000000000001',
            tokenHash: 'token-hash',
            expiresAt: $expiresAt,
        );

        self::assertFalse($session->isRevoked());
        self::assertTrue($session->isActive($now));
        self::assertFalse($session->isExpired($now));
        self::assertSame(
            '01JSESSIONUSER000000000001',
            $session->userId(),
        );
        self::assertSame('token-hash', $session->tokenHash());
        self::assertSame($expiresAt, $session->expiresAt());
    }

    public function test_it_detects_expired_session(): void
    {
        $expiresAt = new DateTimeImmutable(
            '2026-01-01 11:00:00',
            new DateTimeZone('UTC'),
        );

        $session = Session::create(
            sessionId: SessionId::generate(),
            userId: '01JSESSIONUSER000000000001',
            tokenHash: 'token-hash',
            expiresAt: $expiresAt,
        );

        $now = new DateTimeImmutable(
            '2026-01-01 11:00:01',
            new DateTimeZone('UTC'),
        );

        self::assertTrue($session->isExpired($now));
        self::assertFalse($session->isActive($now));
    }

    public function test_it_can_be_revoked(): void
    {
        $now = new DateTimeImmutable(
            '2026-01-01 10:00:00',
            new DateTimeZone('UTC'),
        );

        $session = Session::create(
            sessionId: SessionId::generate(),
            userId: '01JSESSIONUSER000000000001',
            tokenHash: 'token-hash',
            expiresAt: new DateTimeImmutable(
                '2026-01-01 11:00:00',
                new DateTimeZone('UTC'),
            ),
        );

        self::assertTrue($session->isActive($now));

        $session->revoke();

        self::assertTrue($session->isRevoked());
        self::assertFalse($session->isActive($now));
    }

    public function test_it_reconstitutes_revoked_session(): void
    {
        $sessionId = SessionId::generate();

        $expiresAt = new DateTimeImmutable(
            '2026-01-01 11:00:00',
            new DateTimeZone('UTC'),
        );

        $session = Session::reconstitute(
            sessionId: $sessionId,
            userId: '01JSESSIONUSER000000000001',
            tokenHash: 'token-hash',
            expiresAt: $expiresAt,
            revoked: true,
        );

        self::assertTrue($session->isRevoked());
        self::assertSame(
            $sessionId->value(),
            $session->sessionId()->value(),
        );
        self::assertSame(
            '01JSESSIONUSER000000000001',
            $session->userId(),
        );
    }

    public function test_it_rejects_empty_user_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Session user ID is required.');

        Session::create(
            sessionId: SessionId::generate(),
            userId: '',
            tokenHash: 'token-hash',
            expiresAt: new DateTimeImmutable(
                '2026-01-01 11:00:00',
                new DateTimeZone('UTC'),
            ),
        );
    }

    public function test_it_rejects_empty_token_hash(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Session token hash is required.');

        Session::create(
            sessionId: SessionId::generate(),
            userId: '01JSESSIONUSER000000000001',
            tokenHash: '',
            expiresAt: new DateTimeImmutable(
                '2026-01-01 11:00:00',
                new DateTimeZone('UTC'),
            ),
        );
    }
}
