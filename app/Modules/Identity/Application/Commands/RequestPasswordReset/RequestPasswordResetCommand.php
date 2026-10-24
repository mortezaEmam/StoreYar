<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Commands\RequestPasswordReset;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class RequestPasswordResetCommand implements Command
{
    public function __construct(
        public string $email,
    ) {}
}
