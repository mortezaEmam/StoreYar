<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Application\Commands\RevokeAllUserSessions;

use StoreYar\Modules\Identity\Application\Commands\RevokeAllUserSessions\RevokeAllUserSessionsCommand;
use StoreYar\Modules\Identity\Application\Commands\RevokeAllUserSessions\RevokeAllUserSessionsHandler;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use StoreYar\Shared\Application\Bus\Command\Command;
use Tests\TestCase;

final class RevokeAllUserSessionsHandlerTest extends TestCase
{
    public function test_it_revokes_all_sessions_for_user(): void
    {
        $repository = new FakeSessionRepository();

        $handler = new RevokeAllUserSessionsHandler(
            sessions: $repository,
        );

        $result = $handler->handle(
            new RevokeAllUserSessionsCommand(
                userId: '01JSESSIONUSER000000000001',
            ),
        );

        self::assertNull($result);
        self::assertSame(
            '01JSESSIONUSER000000000001',
            $repository->revokedUserId,
        );
    }

    public function test_it_rejects_invalid_command(): void
    {
        $handler = new RevokeAllUserSessionsHandler(
            sessions: new FakeSessionRepository(),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'RevokeAllUserSessionsHandler received an invalid command.',
        );

        $handler->handle(new InvalidCommand());
    }
}

final class FakeSessionRepository implements SessionRepository
{
    public ?string $revokedUserId = null;

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
        $this->revokedUserId = $userId;
    }

    public function findActiveByTokenHash(
        string $tokenHash,
        \DateTimeImmutable $now,
    ): ?SessionId {
        return null;
    }
}

final class InvalidCommand implements Command
{
}
