<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Application\Commands\RotateSession;

use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Identity\Application\Commands\RotateSession\RotateSessionCommand;
use StoreYar\Modules\Identity\Application\Commands\RotateSession\RotateSessionHandler;
use StoreYar\Modules\Identity\Application\Results\SessionRotationResult;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\Contracts\SessionTokenGenerator;
use StoreYar\Modules\Identity\Domain\Entities\Session;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use StoreYar\Shared\Domain\Contracts\Clock;

final class RotateSessionHandlerTest extends TestCase
{
    public function test_it_rotates_active_session(): void
    {
        $now = new \DateTimeImmutable(
            '2026-09-25 12:00:00',
            new \DateTimeZone('UTC'),
        );

        $currentSessionId = SessionId::generate();

        $currentSession = Session::create(
            sessionId: $currentSessionId,
            userId: '01JSESSIONUSER000000000001',
            tokenHash: hash('sha256', 'old-token'),
            expiresAt: $now->modify('+10 days'),
        );

        $sessions = new FakeSessionRepository(
            currentSession: $currentSession,
        );

        $tokenGenerator = new FakeSessionTokenGenerator();

        $handler = new RotateSessionHandler(
            sessions: $sessions,
            tokenGenerator: $tokenGenerator,
            clock: new FakeClock($now),
        );

        $result = $handler->handle(
            new RotateSessionCommand(
                currentSessionId: $currentSessionId,
            ),
        );

        self::assertInstanceOf(
            SessionRotationResult::class,
            $result,
        );

        self::assertSame(
            'new-raw-token',
            $result->token,
        );

        self::assertNotSame(
            $currentSessionId->value(),
            $result->session->sessionId()->value(),
        );

        self::assertSame(
            $currentSession->userId(),
            $result->session->userId(),
        );

        self::assertSame(
            hash('sha256', 'new-raw-token'),
            $result->session->tokenHash(),
        );

        self::assertNotNull($sessions->createdSession);
        self::assertTrue(
            $sessions->createdSession
                ->sessionId()
                ->equals($result->session->sessionId()),
        );

        self::assertNotNull($sessions->revokedSessionId);
        self::assertTrue(
            $sessions->revokedSessionId
                ->equals($currentSessionId),
        );
    }

    public function test_it_rejects_inactive_session(): void
    {
        $now = new \DateTimeImmutable(
            '2026-09-25 12:00:00',
            new \DateTimeZone('UTC'),
        );

        $currentSessionId = SessionId::generate();

        $currentSession = Session::create(
            sessionId: $currentSessionId,
            userId: '01JSESSIONUSER000000000001',
            tokenHash: hash('sha256', 'old-token'),
            expiresAt: $now->modify('-1 minute'),
        );

        $sessions = new FakeSessionRepository(
            currentSession: $currentSession,
        );

        $handler = new RotateSessionHandler(
            sessions: $sessions,
            tokenGenerator: new FakeSessionTokenGenerator(),
            clock: new FakeClock($now),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid session.');

        $handler->handle(
            new RotateSessionCommand(
                currentSessionId: $currentSessionId,
            ),
        );

        self::assertNull($sessions->createdSession);
        self::assertNull($sessions->revokedSessionId);
    }

    public function test_it_rejects_invalid_command(): void
    {
        $handler = new RotateSessionHandler(
            sessions: new FakeSessionRepository(),
            tokenGenerator: new FakeSessionTokenGenerator(),
            clock: new FakeClock(
                new \DateTimeImmutable(
                    '2026-09-25 12:00:00',
                    new \DateTimeZone('UTC'),
                ),
            ),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'RotateSessionHandler received an invalid command.',
        );

        $handler->handle(
            new class implements \StoreYar\Shared\Application\Bus\Command\Command {},
        );
    }
}

final class FakeSessionRepository implements SessionRepository
{
    public ?Session $createdSession = null;

    public ?SessionId $revokedSessionId = null;

    public function __construct(
        private ?Session $currentSession = null,
    ) {}

    public function create(
        SessionId $sessionId,
        string $userId,
        string $tokenHash,
        \DateTimeImmutable $expiresAt,
    ): void {
        $this->createdSession = Session::create(
            sessionId: $sessionId,
            userId: $userId,
            tokenHash: $tokenHash,
            expiresAt: $expiresAt,
        );
    }

    public function revoke(SessionId $sessionId): void
    {
        $this->revokedSessionId = $sessionId;
    }

    public function revokeAllForUser(string $userId): void
    {
    }

    public function findById(SessionId $sessionId): ?Session
    {
        return $this->currentSession;
    }

    public function findActiveByTokenHash(
        string $tokenHash,
        \DateTimeImmutable $now,
    ): ?SessionId {
        return null;
    }
}

final class FakeSessionTokenGenerator implements SessionTokenGenerator
{
    public function generate(): string
    {
        return 'new-raw-token';
    }

    public function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}

final class FakeClock implements Clock
{
    public function __construct(
        private readonly \DateTimeImmutable $now,
    ) {}

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}
