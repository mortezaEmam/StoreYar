# Frontend Audit

## Current state (from package.json, resources/, vite.config.js, public/)

| Item | Reality |
|------|---------|
| Framework | **None** beyond Laravel Blade welcome |
| JS | `resources/js/app.js` (default bootstrap) |
| CSS | `resources/css/app.css` |
| Build | Vite 7 + laravel-vite-plugin |
| Dependencies | axios only (runtime); tailwind/vite tooling in devDependencies possible via lock — **no Vue, no Nuxt, no Pinia** |
| UI | `resources/views/welcome.blade.php` stock Laravel page |
| Auth UX | None |
| API client | None |
| TypeScript | No |

## Target (specified)

Nuxt 4, Vue 3, TypeScript, Pinia (where needed), Nuxt UI, Tailwind, API-first, OpenAPI-typed client.

## Gap

Full greenfield frontend. No migration of components — only API and auth/session/BusinessContext contracts to consume.

## Migration strategy (documentation only)

1. Keep Laravel as API-only (`/api`).
2. New Nuxt app (separate package or `frontend/`) consuming OpenAPI.
3. Auth: store opaque Bearer token; send on each request; handle rotate.
4. Always send `X-Business-Id` after org selection.
5. Idempotency-Key on mutating POSTs (UUID per user action).
6. Permission-aware nav from membership role.
7. No optimistic stock/order updates until API confirms (concurrency/version).

## Page map (proposed, not built)

Login/Register → Org picker → Products → Stock → Orders → Payments → Shipments → Members (admin) → Audit (owner/admin).
