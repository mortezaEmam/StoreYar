<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Queries\ValidateSession;

use StoreYar\Shared\Application\Bus\Query\Query;

final readonly class ValidateSessionQuery implements Query
{
    public function __construct(
        public string $token,
    ) {}
}
