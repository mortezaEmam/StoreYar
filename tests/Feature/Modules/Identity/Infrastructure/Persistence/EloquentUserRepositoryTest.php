<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Identity\Infrastructure\Persistence;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Modules\Identity\Domain\Enums\UserStatus;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;
use StoreYar\Modules\Identity\Infrastructure\Persistence\Eloquent\EloquentUserRepository;
use StoreYar\Shared\Domain\Exceptions\ConcurrencyException;
use StoreYar\Shared\Infrastructure\Concurrency\DatabaseVersionedUpdater;
use Tests\TestCase;

final class EloquentUserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_and_finds_a_user_by_id_without_replaying_events(): void
    {
        $repository = app(EloquentUserRepository::class);

        $now = new DateTimeImmutable('2026-09-17 10:00:00.000000');
        $user = User::create(
            id: UserId::generate(),
            email: 'ali@example.com',
            name: 'Ali',
            now: $now,
        );

        $repository->save($user);

        $found = $repository->findById($user->userId());

        self::assertNotNull($found);
        self::assertTrue($found->userId()->equals($user->userId()));
        self::assertSame('ali@example.com', $found->email());
        self::assertSame('Ali', $found->name());
        self::assertSame(UserStatus::ACTIVE, $found->status());
        self::assertSame(0, $found->version());
        self::assertSame([], $found->domainEvents());
    }

    public function test_it_finds_a_user_by_email(): void
    {
        $repository = app(EloquentUserRepository::class);

        $user = User::create(
            id: UserId::generate(),
            email: 'sara@example.com',
            name: 'Sara',
            now: new DateTimeImmutable('2026-09-17 11:00:00.000000'),
        );

        $repository->save($user);

        $found = $repository->findByEmail('sara@example.com');

        self::assertNotNull($found);
        self::assertTrue($found->userId()->equals($user->userId()));
    }

    public function test_it_updates_an_existing_user_with_optimistic_concurrency(): void
    {
        $repository = app(EloquentUserRepository::class);

        $user = User::create(
            id: UserId::generate(),
            email: 'old@example.com',
            name: 'Old Name',
            now: new DateTimeImmutable('2026-09-17 12:00:00.000000'),
        );

        $repository->save($user);

        $user->updateEmail(
            'new@example.com',
            new DateTimeImmutable('2026-09-17 12:05:00.000000'),
        );

        $repository->save($user);

        $found = $repository->findById($user->userId());

        self::assertNotNull($found);
        self::assertSame('new@example.com', $found->email());
        self::assertSame(1, $found->version());
    }

    public function test_it_rejects_a_stale_user_update(): void
    {
        $repository = app(EloquentUserRepository::class);

        $user = User::create(
            id: UserId::generate(),
            email: 'race@example.com',
            name: 'Race',
            now: new DateTimeImmutable('2026-09-17 13:00:00.000000'),
        );

        $repository->save($user);

        $first = $repository->findById($user->userId());
        $second = $repository->findById($user->userId());

        self::assertNotNull($first);
        self::assertNotNull($second);

        $first->rename(
            'First Update',
            new DateTimeImmutable('2026-09-17 13:01:00.000000'),
        );

        $repository->save($first);

        $second->rename(
            'Second Update',
            new DateTimeImmutable('2026-09-17 13:02:00.000000'),
        );

        $this->expectException(ConcurrencyException::class);

        $repository->save($second);
    }
}
