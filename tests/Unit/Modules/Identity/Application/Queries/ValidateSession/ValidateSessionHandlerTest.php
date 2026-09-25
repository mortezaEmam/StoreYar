<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Application\Queries\ValidateSession;

use StoreYar\Modules\Identity\Application\Queries\ValidateSession\ValidateSessionHandler;
use StoreYar\Modules\Identity\Application\Queries\ValidateSession\ValidateSessionQuery;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\Contracts\SessionTokenGenerator;
use StoreYar\Modules\Identity\Domain\Contracts\UserRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use StoreYar\Shared\Application\Bus\Query\Query;
use StoreYar\Shared\Domain\Contracts\Clock;
use Tests\TestCase;

final class ValidateSessionHandlerTest extends TestCase
{
    public function test_it_returns_active_session_id(): void
    {
        $sessionId = SessionId::generate();
        $clock = new FakeClock();
        $sessions = new FakeSessionRepository();
        $tokenGenerator = new FakeSessionTokenGenerator();
        $users = new FakeUserRepository();

        $sessions->activeSessionId = $sessionId;

        $handler = new ValidateSessionHandler(
            sessions: $sessions,
            tokenGenerator: $tokenGenerator,
            users: $users,
            clock: $clock,
        );

        $result = $handler->handle(
            new ValidateSessionQuery(
                token: 'plain-session-token',
            ),
        );

        self::assertNotNull($result);
        self::assertTrue($sessionId->equals($result));
        self::assertSame(
            hash('sha256', 'plain-session-token'),
            $sessions->searchedTokenHash,
        );
        self::assertSame(
            $clock->now(),
            $sessions->searchedAt,
        );
    }

    public function test_it_returns_null_for_unknown_session(): void
    {
        $sessions = new FakeSessionRepository();
        $tokenGenerator = new FakeSessionTokenGenerator();
        $users = new FakeUserRepository();

        $handler = new ValidateSessionHandler(
            sessions: $sessions,
            tokenGenerator: $tokenGenerator,
            users: $users,
            clock: new FakeClock(),
        );

        $result = $handler->handle(
            new ValidateSessionQuery(
                token: 'unknown-session-token',
            ),
        );

        self::assertNull($result);
    }

    public function test_it_returns_null_for_empty_token(): void
    {
        $sessions = new FakeSessionRepository();

        $handler = new ValidateSessionHandler(
            sessions: $sessions,
            tokenGenerator: new FakeSessionTokenGenerator(),
            users: new FakeUserRepository(),
            clock: new FakeClock(),
        );

        $result = $handler->handle(
            new ValidateSessionQuery(
                token: '',
            ),
        );

        self::assertNull($result);
        self::assertNull($sessions->searchedTokenHash);
    }

    public function test_it_rejects_invalid_query(): void
    {
        $handler = new ValidateSessionHandler(
            sessions: new FakeSessionRepository(),
            tokenGenerator: new FakeSessionTokenGenerator(),
            users: new FakeUserRepository(),
            clock: new FakeClock(),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'ValidateSessionHandler received an invalid query.',
        );

        $handler->handle(new InvalidQuery());
    }
}

final class FakeSessionRepository implements SessionRepository
{
    public ?SessionId $activeSessionId = null;

    public ?string $searchedTokenHash = null;

    public ?\DateTimeImmutable $searchedAt = null;

    public function create(
        SessionId $sessionId,
        string $userId,
        string $tokenHash,
        \DateTimeImmutable $expiresAt,
    ): void {
    }

    public function revoke(SessionId $sessionId): void
    {
    }

    public function revokeAllForUser(string $userId): void
    {
    }

    public function findActiveByTokenHash(
        string $tokenHash,
        \DateTimeImmutable $now,
    ): ?SessionId {
        $this->searchedTokenHash = $tokenHash;
        $this->searchedAt = $now;

        return $this->activeSessionId;
    }
}

final class FakeSessionTokenGenerator implements SessionTokenGenerator
{
    public function generate(): string
    {
        return 'generated-token';
    }

    public function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}

final class FakeUserRepository implements UserRepository
{
    public function findById(
        \StoreYar\Modules\Identity\Domain\ValueObjects\UserId $userId,
    ): ?\StoreYar\Modules\Identity\Domain\Aggregates\User {
        return null;
    }

    public function findByEmail(string $email): ?\StoreYar\Modules\Identity\Domain\Aggregates\User
    {
        return null;
    }

    public function save(
        \StoreYar\Modules\Identity\Domain\Aggregates\User $user,
    ): void {
    }
}

final class FakeClock implements Clock
{
    private \DateTimeImmutable $now;

    public function __construct()
    {
        $this->now = new \DateTimeImmutable(
            '2026-01-01 12:00:00',
            new \DateTimeZone('UTC'),
        );
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}

final class InvalidQuery implements Query
{
}
