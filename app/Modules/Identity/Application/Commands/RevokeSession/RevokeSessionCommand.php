<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Commands\RevokeSession;

use StoreYar\Modules\Identity\Domain\ValueObjects\SessionId;
use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class RevokeSessionCommand implements Command
{
    public function __construct(
        public SessionId $sessionId,
    ) {}
}
