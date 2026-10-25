<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Application\Commands\ClosePosSession;

use StoreYar\Shared\Application\Bus\Command\Command;

final readonly class ClosePosSessionCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $sessionId,
        public int $closingCountAmount,
        public ?string $closedBy = null,
        public ?string $notes = null,
    ) {}
}
