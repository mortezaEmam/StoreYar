# StoreYar frontend (Nuxt 3)

Admin UI for the StoreYar multi-tenant commerce API.

## Stack

- Nuxt 3 + Vue 3 + Pinia
- `useApi` → Laravel `/api` with Bearer + `X-Business-Id` + optional `Idempotency-Key`
- i18n: `locales/en.json`, `locales/fa.json`, `composables/useI18n.ts` (RTL when `fa`)

## Setup

```bash
cd frontend
npm install --legacy-peer-deps
export NUXT_PUBLIC_API_BASE=http://localhost:8000/api
npm run dev
```

Open the URL Nuxt prints (often `http://localhost:3001` if 3000 is busy).

## Auth flow

1. Register / login → token in Pinia + `localStorage`
2. Create or select organization on **Business**
3. All product/stock/order calls send `X-Business-Id`
4. Forgot password → email (or log in local) → **Reset password** with token

## Layout

```
pages/          routes
layouts/        default shell + nav + toast
composables/    useApi, useToast, useI18n
stores/         session
locales/        en.json, fa.json
middleware/     auth
plugins/        session + i18n hydrate
```

## Language

Use the **فا / EN** control on auth cards and the main header. Preference is stored in `localStorage` (`storeyar_locale`).

## Build

```bash
npm run build
```

Requires Node 20+.
