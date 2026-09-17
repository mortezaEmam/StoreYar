<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Identity\Domain\Contracts;

use PHPUnit\Framework\TestCase;
use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Modules\Identity\Domain\Contracts\UserRepository;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;

final class UserRepositoryTest extends TestCase
{
    public function test_contract_can_be_implemented(): void
    {
        $repository = new TestUserRepository();

        $this->assertNull(
            $repository->findById(UserId::generate()),
        );

        $this->assertNull(
            $repository->findByEmail('user@example.com'),
        );
    }
}

final class TestUserRepository implements UserRepository
{
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
}
