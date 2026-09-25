<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Commands\RevokeAllUserSessions;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class RevokeAllUserSessionsCommand implements Command
{
    public function __construct(
        public string $userId,
    ) {}
}
