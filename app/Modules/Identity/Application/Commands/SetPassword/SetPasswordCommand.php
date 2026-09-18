<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Commands\SetPassword;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class SetPasswordCommand implements Command
{
    public function __construct(
        public string $userId,
        public string $password,
    ) {}
}
