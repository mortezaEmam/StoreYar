<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Authorization;

use StoreYar\Shared\Application\Contracts\Authorization\Permission;

final readonly class SimplePermission implements Permission
{
    public function __construct(
        private string $code,
    ) {
        if ($code === '') {
            throw new \InvalidArgumentException(
                'Permission code cannot be empty.'
            );
        }
    }

    public function code(): string
    {
        return $this->code;
    }
}
