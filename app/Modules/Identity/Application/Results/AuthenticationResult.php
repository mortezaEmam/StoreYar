<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Results;

use StoreYar\Modules\Identity\Domain\Aggregates\User;
use StoreYar\Modules\Identity\Domain\Entities\Session;

final readonly class AuthenticationResult
{
    public function __construct(
        public User $user,
        public Session $session,
        public string $token,
    ) {}
}
