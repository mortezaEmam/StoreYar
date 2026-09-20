<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Application\Commands\AuthenticateUser;

use StoreYar\Modules\Identity\Application\Commands\AuthenticateUser\AuthenticateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\AuthenticateUser\AuthenticateUserHandler;
use StoreYar\Modules\Identity\Application\Results\AuthenticationResult;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Modules\Identity\Domain\Contracts\PasswordHasher;
use StoreYar\Modules\Identity\Domain\Contracts\UserCredentialRepository;
use StoreYar\Modules\Identity\Domain\Contracts\UserRepository;
use StoreYar\Modules\Identity\Domain\Enums\UserStatus;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;
use StoreYar\Shared\Application\Bus\Command\Command;
use StoreYar\Modules\Identity\Domain\Contracts\SessionRepository;
use StoreYar\Modules\Identity\Domain\Contracts\SessionTokenGenerator;
use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use StoreYar\Shared\Domain\Contracts\Clock;
use Tests\TestCase;

final class AuthenticateUserHandlerTest extends TestCase
{
    public function test_it_authenticates_user_with_valid_credentials(): void
    {
        $userId = UserId::generate();

        $user = User::reconstitute(
            id: $userId,
            email: 'user@example.com',
            name: 'Test User',
            createdAt: new \DateTimeImmutable('2026-01-01 00:00:00', new \DateTimeZone('UTC')),
            updatedAt: new \DateTimeImmutable('2026-01-01 00:00:00', new \DateTimeZone('UTC')),
            status: UserStatus::ACTIVE,
            version: 0,
        );

        $users = new FakeUserRepository($user);
        $credentials = new FakeUserCredentialRepository('stored-hash');
        $hasher = new FakePasswordHasher(true);

        $sessionRepository = new FakeSessionRepository();
        $tokenGenerator = new FakeSessionTokenGenerator();
        $clock = new FakeClock();

        $handler = new AuthenticateUserHandler(
            users: $users,
            credentials: $credentials,
            passwordHasher: $hasher,
            sessions: $sessionRepository,
            tokenGenerator: $tokenGenerator,
            clock: $clock,
        );

        $result = $handler->handle(
            new AuthenticateUserCommand(
                email: 'user@example.com',
                password: 'plain-secret',
            ),
        );

        self::assertInstanceOf(
            AuthenticationResult::class,
            $result,
        );

        self::assertSame(
            $user->id(),
            $result->user->id(),
        );

        self::assertSame(
            'plain-secret',
            $hasher->receivedPassword,
        );

        self::assertSame(
            'stored-hash',
            $hasher->receivedHash,
        );

        self::assertSame(
            'plain-session-token',
            $result->token,
        );

        self::assertNotNull(
            $sessionRepository->sessionId,
        );

        self::assertSame(
            $user->id(),
            $sessionRepository->userId,
        );

        self::assertSame(
            'hashed-session-token',
            $sessionRepository->tokenHash,
        );

        self::assertSame(
            'plain-session-token',
            $tokenGenerator->generatedToken,
        );
    }

    public function test_it_rejects_invalid_password(): void
    {
        $user = $this->createUser();

        $hasher = new FakePasswordHasher(false);

        $handler = new AuthenticateUserHandler(
            users: new FakeUserRepository($user),
            credentials: new FakeUserCredentialRepository('stored-hash'),
            passwordHasher: $hasher,
            sessions: new FakeSessionRepository(),
            tokenGenerator: new FakeSessionTokenGenerator(),
            clock: new FakeClock(),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid credentials.');

        $handler->handle(
            new AuthenticateUserCommand(
                email: 'user@example.com',
                password: 'wrong-password',
            ),
        );
    }

    public function test_it_rejects_unknown_user(): void
    {
        $hasher = new FakePasswordHasher(true);
        $handler = new AuthenticateUserHandler(
            users: new FakeUserRepository(null),
            credentials: new FakeUserCredentialRepository('stored-hash'),
            passwordHasher: $hasher,
            sessions: new FakeSessionRepository(),
            tokenGenerator: new FakeSessionTokenGenerator(),
            clock: new FakeClock(),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid credentials.');

        $handler->handle(
            new AuthenticateUserCommand(
                email: 'unknown@example.com',
                password: 'plain-secret',
            ),
        );
    }

    public function test_it_rejects_invalid_command(): void
    {
        $hasher = new FakePasswordHasher(true);
        $handler = new AuthenticateUserHandler(
            users: new FakeUserRepository(null),
            credentials: new FakeUserCredentialRepository('stored-hash'),
            passwordHasher: $hasher,
            sessions: new FakeSessionRepository(),
            tokenGenerator: new FakeSessionTokenGenerator(),
            clock: new FakeClock(),
        );

        $this->expectException(\InvalidArgumentException::class);

        $handler->handle(new InvalidCommand());
    }

    private function createUser(): User
    {
        return User::reconstitute(
            id: UserId::generate(),
            email: 'user@example.com',
            name: 'Test User',
            createdAt: new \DateTimeImmutable('2026-01-01 00:00:00', new \DateTimeZone('UTC')),
            updatedAt: new \DateTimeImmutable('2026-01-01 00:00:00', new \DateTimeZone('UTC')),
            status: UserStatus::ACTIVE,
            version: 0,
        );
    }

    public function test_it_rejects_a_suspended_user(): void
    {
        $user = User::reconstitute(
            id: UserId::generate(),
            email: 'suspended@example.com',
            name: 'Suspended User',
            createdAt: new \DateTimeImmutable(
                '2026-01-01 00:00:00',
                new \DateTimeZone('UTC'),
            ),
            updatedAt: new \DateTimeImmutable(
                '2026-01-01 00:00:00',
                new \DateTimeZone('UTC'),
            ),
            status: UserStatus::SUSPENDED,
            version: 0,
        );
        $hasher = new FakePasswordHasher(true);
        $handler = new AuthenticateUserHandler(
            users: new FakeUserRepository($user),
            credentials: new FakeUserCredentialRepository('stored-hash'),
            passwordHasher: $hasher,
            sessions: new FakeSessionRepository(),
            tokenGenerator: new FakeSessionTokenGenerator(),
            clock: new FakeClock(),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid credentials.');

        $handler->handle(
            new AuthenticateUserCommand(
                email: 'suspended@example.com',
                password: 'plain-secret',
            ),
        );
    }
}

final class FakeUserRepository implements UserRepository
{
    public function __construct(
        private readonly ?User $user,
    ) {}

    public function findById(UserId $userId): ?User
    {
        return $this->user?->id() === $userId->value()
            ? $this->user
            : null;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->user?->email() === $email
            ? $this->user
            : null;
    }

    public function save(User $user): void {}
}

final class FakeUserCredentialRepository implements UserCredentialRepository
{
    public function __construct(
        private readonly ?string $passwordHash,
    ) {}

    public function findPasswordHash(UserId $userId): ?string
    {
        return $this->passwordHash;
    }

    public function savePasswordHash(
        UserId $userId,
        string $passwordHash,
    ): void {}
}

final class FakePasswordHasher implements PasswordHasher
{
    public ?string $receivedPassword = null;

    public ?string $receivedHash = null;

    public function __construct(
        private readonly bool $verificationResult,
    ) {}

    public function hash(string $plainPassword): string
    {
        return 'generated-hash';
    }

    public function verify(
        string $plainPassword,
        string $hashedPassword,
    ): bool {
        $this->receivedPassword = $plainPassword;
        $this->receivedHash = $hashedPassword;

        return $this->verificationResult;
    }
}


final class FakeSessionRepository implements SessionRepository
{
    public ?SessionId $sessionId = null;

    public ?string $userId = null;

    public ?string $tokenHash = null;

    public ?\DateTimeImmutable $expiresAt = null;

    public function create(
        SessionId $sessionId,
        string $userId,
        string $tokenHash,
        \DateTimeImmutable $expiresAt,
    ): void {
        $this->sessionId = $sessionId;
        $this->userId = $userId;
        $this->tokenHash = $tokenHash;
        $this->expiresAt = $expiresAt;
    }

    public function revoke(SessionId $sessionId): void {}

    public function findActiveByTokenHash(
        string $tokenHash,
        \DateTimeImmutable $now,
    ): ?SessionId {
        return null;
    }
}

final class FakeSessionTokenGenerator implements SessionTokenGenerator
{
    public string $generatedToken = 'plain-session-token';

    public function generate(): string
    {
        return $this->generatedToken;
    }

    public function hash(string $plainToken): string
    {
        return 'hashed-session-token';
    }
}

final class FakeClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(
            '2026-01-01 12:00:00',
            new \DateTimeZone('UTC'),
        );
    }
}

final class InvalidCommand implements Command
{
}
