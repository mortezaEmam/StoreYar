<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Queries\GetUserById;

use StoreYar\Shared\Application\Bus\Query\Query;

final readonly class GetUserByIdQuery implements Query
{
    public function __construct(
        public string $userId,
    ) {}
}
