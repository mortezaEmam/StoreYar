<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Infrastructure\Security;

use StoreYar\Modules\Identity\Application\Security\PasswordResetNotifier;

final class ArrayPasswordResetNotifier implements PasswordResetNotifier
{
    /** @var array<string, string> email => last plain token */
    private array $tokens = [];

    public function notify(string $email, string $plainToken): void
    {
        $this->tokens[strtolower($email)] = $plainToken;
    }

    public function lastTokenFor(string $email): ?string
    {
        return $this->tokens[strtolower($email)] ?? null;
    }
}
