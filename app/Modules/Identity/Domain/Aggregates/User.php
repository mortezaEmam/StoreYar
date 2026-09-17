<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Domain\Aggregates;

use DateTimeImmutable;
use StoreYar\Modules\Identity\Domain\Enums\UserStatus;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;
use StoreYar\Shared\Domain\Aggregates\AggregateRoot;
use StoreYar\Modules\Identity\Domain\Events\UserActivated;
use StoreYar\Modules\Identity\Domain\Events\UserCreated;
use StoreYar\Modules\Identity\Domain\Events\UserSuspended;

final class User extends AggregateRoot
{
    private UserStatus $status;

    private int $version = 0;

    private function __construct(
        private readonly UserId $userId,
        private string $email,
        private string $name,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        UserStatus $status,
    ) {
        parent::__construct((string) $userId);

        $this->status = $status;
    }

    public static function create(
        UserId $id,
        string $email,
        string $name,
        DateTimeImmutable $now,
    ): self {
        self::assertRequired($email, 'User email');
        self::assertRequired($name, 'User name');

        $user = new self(
            userId: $id,
            email: $email,
            name: $name,
            createdAt: $now,
            updatedAt: $now,
            status: UserStatus::ACTIVE,
        );

        $user->recordEvent(
            new UserCreated(
                userId: $user->id(),
                email: $user->email(),
                occurredAt: $now,
            ),
        );

        return $user;
    }

    public function suspend(DateTimeImmutable $now): void
    {
        if ($this->status !== UserStatus::ACTIVE) {
            throw new \LogicException(
                'Only an active user can be suspended.',
            );
        }

        $this->status = UserStatus::SUSPENDED;
        $this->updatedAt = $now;
        $this->version++;

        $this->recordEvent(
            new UserSuspended(
                userId: $this->id(),
                occurredAt: $now,
            ),
        );
    }

    public function activate(DateTimeImmutable $now): void
    {
        if ($this->status !== UserStatus::SUSPENDED) {
            throw new \LogicException(
                'Only a suspended user can be activated.',
            );
        }

        $this->status = UserStatus::ACTIVE;
        $this->updatedAt = $now;
        $this->version++;

        $this->recordEvent(
            new UserActivated(
                userId: $this->id(),
                occurredAt: $now,
            ),
        );
    }


    public function updateEmail(
        string $email,
        DateTimeImmutable $now,
    ): void {
        self::assertRequired($email, 'User email');

        $this->email = $email;
        $this->updatedAt = $now;
        $this->version++;
    }

    public function rename(
        string $name,
        DateTimeImmutable $now,
    ): void {
        self::assertRequired($name, 'User name');

        $this->name = $name;
        $this->updatedAt = $now;
        $this->version++;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function status(): UserStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function version(): int
    {
        return $this->version;
    }

    private static function assertRequired(
        string $value,
        string $field,
    ): void {
        if (trim($value) === '') {
            throw new \InvalidArgumentException(
                $field . ' is required.',
            );
        }
    }
}
