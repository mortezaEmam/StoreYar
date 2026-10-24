<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Application\Security;

/**
 * Delivers the plain reset token out-of-band (email in production).
 * Default implementation logs; tests may bind a capturing notifier.
 */
interface PasswordResetNotifier
{
    public function notify(string $email, string $plainToken): void;
}
