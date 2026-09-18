<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Application\Queries\GetUserById;

use DateTimeImmutable;
use StoreYar\Modules\Identity\Application\Queries\GetUserById\GetUserByIdHandler;
use StoreYar\Modules\Identity\Application\Queries\GetUserById\GetUserByIdQuery;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Modules\Identity\Domain\Contracts\UserRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;
use Tests\TestCase;

final class GetUserByIdHandlerTest extends TestCase
{
    public function test_it_finds_user_by_id(): void
    {
        $userId = UserId::generate();

        $user = User::create(
            id: $userId,
            email: 'ali@example.com',
            name: 'Ali',
            now: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
        );

        $repository = new class($user) implements UserRepository {
            public function __construct(
                private readonly User $user,
            ) {}

            public function findById(UserId $id): ?User
            {
                return $id->equals($this->user->userId())
                    ? $this->user
                    : null;
            }

            public function findByEmail(string $email): ?User
            {
                return null;
            }

            public function save(User $user): void
            {
            }
        };

        $handler = new GetUserByIdHandler($repository);

        $result = $handler->handle(
            new GetUserByIdQuery(
                userId: $userId->value(),
            ),
        );

        self::assertSame($user, $result);
        self::assertSame($userId->value(), $result?->id());
        self::assertSame('ali@example.com', $result?->email());
    }

    public function test_it_returns_null_when_user_does_not_exist(): void
    {
        $repository = new class implements UserRepository {
            public function findById(UserId $id): ?User
            {
                return null;
            }

            public function findByEmail(string $email): ?User
            {
                return null;
            }

            public function save(User $user): void
            {
            }
        };

        $handler = new GetUserByIdHandler($repository);

        $result = $handler->handle(
            new GetUserByIdQuery(
                userId: UserId::generate()->value(),
            ),
        );

        self::assertNull($result);
    }
}
