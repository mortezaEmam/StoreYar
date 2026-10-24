# Frontend E2E (Playwright)

## Install (once)

```bash
cd frontend
npm install --legacy-peer-deps
npm install -D @playwright/test
npx playwright install chromium
```

Ensure `package.json` scripts include:

```json
"test:e2e": "playwright test"
```

## Run smoke (UI only)

```bash
# terminal 1
cd frontend && NUXT_PUBLIC_API_BASE=http://localhost:8000/api npm run dev

# terminal 2
cd frontend
E2E_BASE_URL=http://localhost:3001 npm run test:e2e
```

(Use the port Nuxt prints.)

## What is covered

- Login / register / forgot-password pages render
- Language-agnostic selectors (email/password fields)

Full commerce path remains covered by PHP Feature/E2E tests against the API.
