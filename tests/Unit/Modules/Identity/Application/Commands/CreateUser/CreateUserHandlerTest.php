<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Application\Commands\CreateUser;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserCommand;
use StoreYar\Modules\Identity\Application\Commands\CreateUser\CreateUserHandler;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Modules\Identity\Domain\Contracts\UserRepository;
use StoreYar\Shared\Domain\Contracts\Clock;

final class CreateUserHandlerTest extends TestCase
{
    public function test_it_creates_and_persists_a_user(): void
    {
        $now = new DateTimeImmutable('2026-09-18 10:00:00.000000');

        $clock = new class ($now) implements Clock {
            public function __construct(
                private readonly DateTimeImmutable $now,
            ) {
            }

            public function now(): DateTimeImmutable
            {
                return $this->now;
            }
        };

        $repository = new class implements UserRepository {
            public ?User $savedUser = null;

            public function findById(
                \StoreYar\Modules\Identity\Domain\ValueObjects\UserId $id,
            ): ?User {
                return null;
            }

            public function findByEmail(string $email): ?User
            {
                return null;
            }

            public function save(User $user): void
            {
                $this->savedUser = $user;
            }
        };

        $handler = new CreateUserHandler(
            users: $repository,
            clock: $clock,
        );

        $result = $handler->handle(
            new CreateUserCommand(
                email: 'ali@example.com',
                name: 'Ali',
            ),
        );

        self::assertInstanceOf(User::class, $result);
        self::assertNotNull($repository->savedUser);
        self::assertSame($result, $repository->savedUser);
        self::assertTrue($result->userId()->value() !== '');
        self::assertSame('ali@example.com', $result->email());
        self::assertSame('Ali', $result->name());
        self::assertSame($now, $result->createdAt());
        self::assertSame($now, $result->updatedAt());
        self::assertSame(0, $result->version());
        self::assertCount(1, $result->domainEvents());
    }
}
