<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Infrastructure\Security;

use Illuminate\Support\Facades\Log;
use StoreYar\Modules\Identity\Application\Security\PasswordResetNotifier;

final class LogPasswordResetNotifier implements PasswordResetNotifier
{
    public function notify(string $email, string $plainToken): void
    {
        // Never log the raw token in production-like channels; only that a reset was issued.
        Log::info('password_reset.issued', [
            'email' => $email,
            // Token omitted from logs by design.
        ]);

        if (app()->environment('local', 'testing')) {
            Log::debug('password_reset.token_for_dev', [
                'email' => $email,
                'token' => $plainToken,
            ]);
        }
    }
}
