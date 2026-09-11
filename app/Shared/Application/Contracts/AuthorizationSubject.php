<?php

declare(strict_types=1);

namespace StoreYar\Shared\Application\Contracts;

interface AuthorizationSubject
{
    public function id(): string;

    public function type(): string;
}
