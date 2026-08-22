import { test, expect } from '../../fixtures';

test.describe('WurzelwerkManagerLogin', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test.skip('Grünes Netz not testable right now', async () => {});

    test('login via manager page (placeholder)', async ({ page }) => {
        await page.goto('/antragsgruen_sites/manager/index');
        await page.locator('#loginLink').click();
        await expect(page.locator('h1')).toContainText(/Login/i);
        await page.locator('#gruenesNetzAccount').fill('DoeJane');
        await page.locator('#gruenesNetzLoginForm [name="gruenesNetzLogin"]').click();
        await expect(page.locator('#logoutLink')).toBeVisible();
    });
});