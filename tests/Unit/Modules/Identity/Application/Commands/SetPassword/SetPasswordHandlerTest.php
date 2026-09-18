<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Application\Commands\SetPassword;

use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordCommand;
use StoreYar\Modules\Identity\Application\Commands\SetPassword\SetPasswordHandler;
use StoreYar\Modules\Identity\Domain\Contracts\PasswordHasher;
use StoreYar\Modules\Identity\Domain\Contracts\UserCredentialRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;
use StoreYar\Shared\Application\Bus\Command\Command;
use Tests\TestCase;

final class SetPasswordHandlerTest extends TestCase
{
    public function test_it_hashes_password_and_persists_the_hash(): void
    {
        $userId = UserId::generate();

        $hasher = new FakePasswordHasher();
        $repository = new FakeUserCredentialRepository();

        $handler = new SetPasswordHandler(
            passwordHasher: $hasher,
            credentials: $repository,
        );

        $command = new SetPasswordCommand(
            userId: (string) $userId,
            password: 'plain-secret',
        );

        $result = $handler->handle($command);

        self::assertNull($result);
        self::assertSame('plain-secret', $hasher->receivedPassword);
        self::assertSame($userId->value(), $repository->savedUserId);
        self::assertSame('generated-hash', $repository->savedPasswordHash);
    }

    public function test_it_rejects_an_invalid_command(): void
    {
        $handler = new SetPasswordHandler(
            passwordHasher: new FakePasswordHasher(),
            credentials: new FakeUserCredentialRepository(),
        );

        $this->expectException(\InvalidArgumentException::class);

        $handler->handle(new InvalidCommand());
    }
}

final class FakePasswordHasher implements PasswordHasher
{
    public ?string $receivedPassword = null;

    public function hash(string $plainPassword): string
    {
        $this->receivedPassword = $plainPassword;

        return 'generated-hash';
    }

    public function verify(
        string $plainPassword,
        string $hashedPassword,
    ): bool {
        return false;
    }
}

final class FakeUserCredentialRepository implements UserCredentialRepository
{
    public ?string $savedUserId = null;

    public ?string $savedPasswordHash = null;

    public function findPasswordHash(UserId $userId): ?string
    {
        return null;
    }

    public function savePasswordHash(
        UserId $userId,
        string $passwordHash,
    ): void {
        $this->savedUserId = $userId->value();
        $this->savedPasswordHash = $passwordHash;
    }
}

final class InvalidCommand implements Command
{
}
