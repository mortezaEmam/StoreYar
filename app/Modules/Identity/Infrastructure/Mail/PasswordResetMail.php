<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class PasswordResetMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $plainToken,
        public readonly string $email,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'StoreYar password reset',
        );
    }

    public function content(): Content
    {
        $frontend = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')), '/');
        $link = $frontend.'/reset-password?email='.urlencode($this->email).'&token='.urlencode($this->plainToken);

        return new Content(
            htmlString: '<p>You requested a password reset for StoreYar.</p>'
                .'<p>Token: <code>'.e($this->plainToken).'</code></p>'
                .'<p><a href="'.e($link).'">Reset password</a></p>'
                .'<p>This token expires in 60 minutes. If you did not request this, ignore this email.</p>',
        );
    }
}
