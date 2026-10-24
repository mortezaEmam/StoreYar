<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Commands\ResetPassword;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class ResetPasswordCommand implements Command
{
    public function __construct(
        public string $email,
        public string $token,
        public string $password,
    ) {}
}
