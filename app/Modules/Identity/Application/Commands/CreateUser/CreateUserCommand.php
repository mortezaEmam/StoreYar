<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Commands\CreateUser;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class CreateUserCommand implements Command
{
    public function __construct(
        public string $email,
        public string $name,
    ) {
    }
}
