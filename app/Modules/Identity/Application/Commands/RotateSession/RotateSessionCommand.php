<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Commands\RotateSession;

use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class RotateSessionCommand implements Command
{
    public function __construct(
        public SessionId $currentSessionId,
    ) {}
}
