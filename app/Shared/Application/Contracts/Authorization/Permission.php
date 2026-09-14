<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Contracts\Authorization;

interface Permission
{
    public function code(): string;
}
