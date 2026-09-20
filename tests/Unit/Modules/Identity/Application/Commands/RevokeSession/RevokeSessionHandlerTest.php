<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Application\Commands\RevokeSession;

use StoreYar\Modules\Identity\Application\Commands\RevokeSession\RevokeSessionCommand;
use StoreYar\Modules\Identity\Application\Commands\RevokeSession\RevokeSessionHandler;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use StoreYar\Shared\Application\Bus\Command\Command;
use Tests\TestCase;

final class RevokeSessionHandlerTest extends TestCase
{
    public function test_it_revokes_session(): void
    {
        $sessionId = SessionId::generate();
        $repository = new FakeSessionRepository();

        $handler = new RevokeSessionHandler(
            sessions: $repository,
        );

        $result = $handler->handle(
            new RevokeSessionCommand(
                sessionId: $sessionId,
            ),
        );

        self::assertNull($result);
        self::assertTrue($repository->revoked);
        self::assertNotNull($repository->revokedSessionId);
        self::assertTrue(
            $sessionId->equals($repository->revokedSessionId),
        );
    }

    public function test_it_rejects_invalid_command(): void
    {
        $handler = new RevokeSessionHandler(
            sessions: new FakeSessionRepository(),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'RevokeSessionHandler received an invalid command.',
        );

        $handler->handle(new InvalidCommand());
    }
}

final class FakeSessionRepository implements SessionRepository
{
    public bool $revoked = false;

    public ?SessionId $revokedSessionId = null;

    public function create(
        SessionId $sessionId,
        string $userId,
        string $tokenHash,
        \DateTimeImmutable $expiresAt,
    ): void {
    }

    public function revoke(SessionId $sessionId): void
    {
        $this->revoked = true;
        $this->revokedSessionId = $sessionId;
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
