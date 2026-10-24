# Ops checklist (StoreYar)

## Environment

| Variable | Purpose |
|----------|---------|
| `APP_KEY` | Laravel encryption |
| `DB_*` | MySQL |
| `FRONTEND_URL` | Links inside password-reset emails |
| `NUXT_PUBLIC_API_BASE` | Browser → API base (e.g. `https://api.example.com/api`) |
| `MAIL_MAILER` | `smtp` / `log` / `array` |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` | SMTP |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | From header |

## Before production

- [ ] `APP_DEBUG=false`
- [ ] Strong `APP_KEY`, secrets not in git
- [ ] HTTPS only; CORS limited to real frontend origins (`config/cors.php`)
- [ ] Mail works end-to-end for password reset (no token in logs)
- [ ] `php artisan migrate --force`
- [ ] `php artisan test` green on CI
- [ ] `cd frontend && npm run build` green
- [ ] Rate limits on auth routes remain enabled
- [ ] Backups for MySQL

## Smoke after deploy

1. `POST /api/auth/register` + login  
2. Create organization, set `X-Business-Id`  
3. Create product → stock → order → confirm → payment → shipment  
4. Forgot password receives email; reset works; old password fails  
5. Response headers include `X-Request-Id`  
