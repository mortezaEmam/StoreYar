<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Infrastructure\Security;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use StoreYar\Modules\Identity\Application\Security\PasswordResetNotifier;
use StoreYar\Modules\Identity\Infrastructure\Mail\PasswordResetMail;

/**
 * Sends reset token via Laravel Mail.
 * Falls back to log if mail fails (never breaks forgot-password HTTP flow).
 */
final class MailPasswordResetNotifier implements PasswordResetNotifier
{
    public function notify(string $email, string $plainToken): void
    {
        try {
            Mail::to($email)->send(new PasswordResetMail($plainToken, $email));
            Log::info('password_reset.mail_sent', ['email' => $email]);
        } catch (\Throwable $e) {
            Log::error('password_reset.mail_failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            if (app()->environment('local', 'testing')) {
                Log::debug('password_reset.token_for_dev', [
                    'email' => $email,
                    'token' => $plainToken,
                ]);
            }
        }
    }
}
