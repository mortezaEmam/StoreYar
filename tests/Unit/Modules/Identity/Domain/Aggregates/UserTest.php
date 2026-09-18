<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Domain\Aggregates;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Modules\Identity\Domain\Enums\UserStatus;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;
use StoreYar\Modules\Identity\Domain\Events\UserActivated;
use StoreYar\Modules\Identity\Domain\Events\UserCreated;
use StoreYar\Modules\Identity\Domain\Events\UserSuspended;

final class UserTest extends TestCase
{
    public function test_it_creates_an_active_user(): void
    {
        $now = new DateTimeImmutable('2026-01-01 10:00:00');

        $user = User::create(
            id: UserId::generate(),
            email: 'user@example.com',
            name: 'Test User',
            now: $now,
        );

        $this->assertSame('user@example.com', $user->email());
        $this->assertSame('Test User', $user->name());
        $this->assertSame(UserStatus::ACTIVE, $user->status());
        $this->assertSame($now, $user->createdAt());
        $this->assertSame($now, $user->updatedAt());
        $this->assertSame(0, $user->version());

    }

    public function test_it_rejects_empty_email(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        User::create(
            id: UserId::generate(),
            email: '   ',
            name: 'Test User',
            now: new DateTimeImmutable(),
        );
    }

    public function test_it_rejects_empty_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        User::create(
            id: UserId::generate(),
            email: 'user@example.com',
            name: '',
            now: new DateTimeImmutable(),
        );
    }

    public function test_it_can_be_suspended(): void
    {
        $createdAt = new DateTimeImmutable('2026-01-01 10:00:00');
        $suspendedAt = new DateTimeImmutable('2026-01-01 11:00:00');

        $user = User::create(
            id: UserId::generate(),
            email: 'user@example.com',
            name: 'Test User',
            now: $createdAt,
        );

        $user->suspend($suspendedAt);

        $this->assertSame(UserStatus::SUSPENDED, $user->status());
        $this->assertSame($suspendedAt, $user->updatedAt());
        $this->assertSame(1, $user->version());
    }

    public function test_it_can_be_activated_after_suspension(): void
    {
        $createdAt = new DateTimeImmutable('2026-01-01 10:00:00');
        $suspendedAt = new DateTimeImmutable('2026-01-01 11:00:00');
        $activatedAt = new DateTimeImmutable('2026-01-01 12:00:00');

        $user = User::create(
            id: UserId::generate(),
            email: 'user@example.com',
            name: 'Test User',
            now: $createdAt,
        );

        $user->suspend($suspendedAt);
        $user->activate($activatedAt);

        $this->assertSame(UserStatus::ACTIVE, $user->status());
        $this->assertSame($activatedAt, $user->updatedAt());
        $this->assertSame(2, $user->version());
        $this->assertSame(
            $user->userId()->value(),
            $user->id(),
        );

    }

    public function test_it_rejects_suspending_an_already_suspended_user(): void
    {
        $user = User::create(
            id: UserId::generate(),
            email: 'user@example.com',
            name: 'Test User',
            now: new DateTimeImmutable(),
        );

        $user->suspend(new DateTimeImmutable());

        $this->expectException(\LogicException::class);

        $user->suspend(new DateTimeImmutable());
    }

    public function test_it_rejects_activating_an_already_active_user(): void
    {
        $user = User::create(
            id: UserId::generate(),
            email: 'user@example.com',
            name: 'Test User',
            now: new DateTimeImmutable(),
        );

        $this->expectException(\LogicException::class);

        $user->activate(new DateTimeImmutable());
    }

    public function test_it_can_update_email(): void
    {
        $createdAt = new DateTimeImmutable('2026-01-01 10:00:00');
        $updatedAt = new DateTimeImmutable('2026-01-01 11:00:00');

        $user = User::create(
            id: UserId::generate(),
            email: 'old@example.com',
            name: 'Test User',
            now: $createdAt,
        );

        $user->updateEmail('new@example.com', $updatedAt);

        $this->assertSame('new@example.com', $user->email());
        $this->assertSame($updatedAt, $user->updatedAt());
        $this->assertSame(1, $user->version());
    }

    public function test_it_can_rename(): void
    {
        $createdAt = new DateTimeImmutable('2026-01-01 10:00:00');
        $updatedAt = new DateTimeImmutable('2026-01-01 11:00:00');

        $user = User::create(
            id: UserId::generate(),
            email: 'user@example.com',
            name: 'Old Name',
            now: $createdAt,
        );

        $user->rename('New Name', $updatedAt);

        $this->assertSame('New Name', $user->name());
        $this->assertSame($updatedAt, $user->updatedAt());
        $this->assertSame(1, $user->version());
    }


    public function test_it_rejects_empty_email_when_updating(): void
    {
        $user = User::create(
            id: UserId::generate(),
            email: 'user@example.com',
            name: 'Test User',
            now: new DateTimeImmutable(),
        );

        $this->expectException(\InvalidArgumentException::class);

        $user->updateEmail('   ', new DateTimeImmutable());
    }

    public function test_it_rejects_empty_name_when_renaming(): void
    {
        $user = User::create(
            id: UserId::generate(),
            email: 'user@example.com',
            name: 'Test User',
            now: new DateTimeImmutable(),
        );

        $this->expectException(\InvalidArgumentException::class);

        $user->rename('', new DateTimeImmutable());
    }


    public function test_creation_records_user_created_event(): void
    {
        $user = User::create(
            id: UserId::generate(),
            email: 'user@example.com',
            name: 'Test User',
            now: new DateTimeImmutable('2026-01-01 10:00:00'),
        );

        $events = $user->domainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserCreated::class, $events[0]);
        $this->assertSame($user->id(), $events[0]->aggregateId());
        $this->assertSame('identity.user', $events[0]->aggregateType());
    }



    public function test_suspension_records_user_suspended_event(): void
    {
        $user = User::create(
            id: UserId::generate(),
            email: 'user@example.com',
            name: 'Test User',
            now: new DateTimeImmutable('2026-01-01 10:00:00'),
        );

        $user->pullDomainEvents();

        $user->suspend(
            new DateTimeImmutable('2026-01-01 11:00:00'),
        );

        $events = $user->domainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserSuspended::class, $events[0]);
        $this->assertSame($user->id(), $events[0]->aggregateId());
    }




    public function test_activation_records_user_activated_event(): void
    {
        $user = User::create(
            id: UserId::generate(),
            email: 'user@example.com',
            name: 'Test User',
            now: new DateTimeImmutable('2026-01-01 10:00:00'),
        );

        $user->suspend(
            new DateTimeImmutable('2026-01-01 11:00:00'),
        );

        $user->pullDomainEvents();

        $user->activate(
            new DateTimeImmutable('2026-01-01 12:00:00'),
        );

        $events = $user->domainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserActivated::class, $events[0]);
        $this->assertSame($user->id(), $events[0]->aggregateId());
    }



    public function test_it_can_be_reconstituted_without_recording_events(): void
    {
        $id = UserId::generate();
        $createdAt = new DateTimeImmutable('2026-01-01 10:00:00');
        $updatedAt = new DateTimeImmutable('2026-01-02 10:00:00');

        $user = User::reconstitute(
            id: $id,
            email: 'user@example.com',
            name: 'John Doe',
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            status: UserStatus::SUSPENDED,
            version: 7,
        );

        $this->assertSame((string) $id, $user->id());
        $this->assertSame($id->value(), $user->userId()->value());
        $this->assertSame('user@example.com', $user->email());
        $this->assertSame('John Doe', $user->name());
        $this->assertSame(UserStatus::SUSPENDED, $user->status());
        $this->assertSame($createdAt, $user->createdAt());
        $this->assertSame($updatedAt, $user->updatedAt());
        $this->assertSame(7, $user->version());
        $this->assertSame([], $user->domainEvents());
    }
}
