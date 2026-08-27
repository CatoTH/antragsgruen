import { test, expect } from '../../fixtures';
import { loginAsStdUser, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';

test.describe('User: account delete', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('delete own account', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdUser(page);

        await page.locator('#myAccountLink').click();
        await expect(page.locator('.breadcrumb')).toContainText('Einstellungen');

        await page.locator('input[name=accountDeleteConfirm]').first().check();
        await page.locator('.accountDeleteForm [name="accountDelete"]').click();
        await expect(page.locator('.breadcrumb').getByText('Einstellungen').filter({ visible: true })).toHaveCount(0);

        await new ConsultationHomePage(page).open();
        await page.locator('#loginLink').click();
        await page.locator('#username').first().fill('testuser@example.org');
        await page.locator('#passwordInput').first().fill('testuser');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await expect(page.locator('body')).toContainText('Benutzer*innenname nicht gefunden');
    });
});