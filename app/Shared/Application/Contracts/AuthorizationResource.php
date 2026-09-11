<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Contracts;

interface AuthorizationResource
{
    public function type(): string;

    public function id(): ?string;
}
