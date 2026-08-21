import { test, expect } from '../../fixtures';

test.describe('CreateAccountFromManager', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create an account from the manager page', async ({ page }) => {
        await page.goto('/antragsgruen_sites/manager/index');
        await page.locator('#loginLink').click();
        await expect(page.locator('#name')).toHaveCount(0);
        await page.locator('#createAccount').click();
        await expect(page.locator('#name')).toBeVisible();

        await page.locator('#username').fill('newuser@example.org');
        await page.locator('#passwordInput').fill('newuser2');
        await page.locator('#passwordConfirm').fill('newuser2');
        await page.locator('#name').fill('New User');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

        await expect(page.locator('h1')).toContainText('Zugang bestätigen');
    });
});