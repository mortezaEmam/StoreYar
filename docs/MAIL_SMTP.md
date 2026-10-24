# Password-reset mail (SMTP)

## Behavior

- `PasswordResetNotifier` in **testing** → `ArrayPasswordResetNotifier` (no network)
- Otherwise → `MailPasswordResetNotifier` via Laravel `Mail`
- Forgot-password HTTP always returns **202**; mail failures are logged, not exposed to the client

## Local development

```env
MAIL_MAILER=log
FRONTEND_URL=http://localhost:3001
MAIL_FROM_ADDRESS=noreply@storeyar.test
MAIL_FROM_NAME=StoreYar
```

Tokens appear in `storage/logs/laravel.log` only when `APP_ENV=local` (debug line).

## Staging / production SMTP example

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=storeyar
MAIL_PASSWORD=secret
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="StoreYar"
FRONTEND_URL=https://app.yourdomain.com
```

## Verify

```bash
php artisan tinker
>>> Mail::raw('ping', fn ($m) => $m->to('you@example.com')->subject('test'));
```

Then:

```bash
curl -X POST http://localhost:8000/api/auth/forgot-password \
  -H 'Content-Type: application/json' \
  -d '{"email":"existing-user@example.com"}'
```

Expect 202 and an email with reset link: `{FRONTEND_URL}/reset-password?email=...&token=...`

## Security

- Never enable token debug logs outside `local` / `testing`
- Use short-lived tokens (60 minutes)
- Prefer transactional ESP (Postmark, SES, Mailgun) over raw SMTP in production
