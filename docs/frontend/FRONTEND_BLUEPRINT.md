# Frontend Blueprint (PROPOSED)

## Current (VERIFIED)
Blade welcome, Vite, axios only. No SPA.

## Target stack
- Nuxt 4, Vue 3, TypeScript
- Nuxt UI + Tailwind CSS
- Pinia only for session token, selected business, membership role
- Typed client from OpenAPI (or hand-written mirrors until contract tests exist)

## When to build
After P0/P1 API integrity (tenant tests, inventory ports, stable auth). Not Phase 1 implementation.

## Application layout
- Auth layout: login / register
- App shell: business switcher, nav by role, main content
- Modules: Products, Stock, Orders, Payments, Shipments, Members, Audit (owner/admin)

## Auth flow
1. Login → store opaque token (memory + secure storage strategy TBD)
2. `Authorization: Bearer`
3. `/api/auth/me` for user
4. List organizations → select business → persist `X-Business-Id`
5. Rotate on schedule/401 policy

## API client
- Inject auth + business headers
- Surface 409 idempotency conflicts, 403/404 tenant misses, version conflicts as user-visible errors
- Mutating POSTs: generate Idempotency-Key per user action (UUID)

## UX rules
- Backend is authority — no client-side stock math as source of truth
- Confirm order: disable double-submit; show insufficient stock errors from API
- Loading / empty states per list
- Permission-aware nav (hide admin routes for members)

## Tests
- Component tests for forms
- Contract tests against OpenAPI when available
- E2E critical path later

## Migration
Greenfield `frontend/` or separate repo consuming `/api`. No Blade feature UI expansion.
