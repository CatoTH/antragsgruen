import { test, expect } from '../../fixtures';

test.describe('CreateAccountFromManager', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create an account from the manager page', async ({ page }) => {
        await page.goto('/antragsgruen_sites/manager/index');
        await page.locator('#loginLink').click();
        await expect(page.locator('#name').filter({ visible: true })).toHaveCount(0);
        await page.locator('#createAccount').click();
        await expect(page.locator('#name').first()).toBeVisible();

        await page.locator('#username').first().fill('newuser@example.org');
        await page.locator('#passwordInput').first().fill('newuser2');
        await page.locator('#passwordConfirm').first().fill('newuser2');
        await page.locator('#name').first().fill('New User');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

        await expect(page.locator('h1')).toContainText('Zugang bestätigen');
    });
});