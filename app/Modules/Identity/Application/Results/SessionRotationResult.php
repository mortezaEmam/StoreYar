<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Results;

use StoreYar\Modules\Identity\Domain\Entities\Session;

final readonly class SessionRotationResult
{
    public function __construct(
        public Session $session,
        public string $token,
    ) {}
}
