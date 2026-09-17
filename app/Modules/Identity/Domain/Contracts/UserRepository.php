<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Domain\Contracts;

use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Modules\Identity\Domain\ValueObjects\UserId;

interface UserRepository
{
    public function findById(UserId $id): ?User;

    public function findByEmail(string $email): ?User;

    public function save(User $user): void;
}
