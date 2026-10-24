# Deploy outline (StoreYar)

## Prerequisites

- PHP 8.4+, Composer, MySQL 8
- Node 20+ for frontend build
- Process manager (systemd, Forge, Docker, etc.)

## Backend

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env   # fill secrets
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
# queue worker if you process outbox asynchronously
php artisan serve   # or php-fpm + nginx
```

## Frontend

```bash
cd frontend
npm ci --legacy-peer-deps   # or npm install --legacy-peer-deps
NUXT_PUBLIC_API_BASE=https://api.example.com/api npm run build
node .output/server/index.mjs   # or static host of .output/public if configured
```

## Required env

See `docs/OPS_CHECKLIST.md` and `docs/MAIL_SMTP.md`.

Critical:

- `APP_URL`, `FRONTEND_URL`
- `DB_*`
- `MAIL_*` for password reset
- CORS origins matching the real SPA origin

## Post-deploy smoke

```bash
export API_BASE=https://api.example.com/api
bash scripts/smoke-api.sh
```

## CI

GitHub Actions (`.github/workflows/ci.yml`) runs PHPUnit + frontend production build on push/PR to `master`.
