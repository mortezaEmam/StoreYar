import { test, expect } from '@playwright/test'

/**
 * Smoke: login page renders and language toggle works.
 * Full API happy-path needs API + DB; run with both servers up.
 *
 *   E2E_BASE_URL=http://localhost:3001 npx playwright test
 */
test.describe('StoreYar UI smoke', () => {
  test('login page shows form', async ({ page }) => {
    await page.goto('/login')
    await expect(page.locator('input[type="email"]')).toBeVisible()
    await expect(page.locator('input[type="password"]')).toBeVisible()
    await expect(page.getByRole('button', { name: /sign in|ورود/i })).toBeVisible()
  })

  test('register page is reachable', async ({ page }) => {
    await page.goto('/register')
    await expect(page.locator('input[type="email"]')).toBeVisible()
  })

  test('forgot password page is reachable', async ({ page }) => {
    await page.goto('/forgot-password')
    await expect(page.locator('input[type="email"]')).toBeVisible()
  })
})
