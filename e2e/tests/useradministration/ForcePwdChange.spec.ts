import { test, expect } from '../../fixtures';
import { loginAsGlobalAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';

test.describe('Useradmin: ForcePwdChange', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('force password change', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsGlobalAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/admin');
        await page.locator('.siteUsers').click();
        await page.locator('.user1').waitFor({ timeout: 10_000 });
        await page.locator('.user1 .btnEdit').click();
        await page.locator('.editUserModal.in').waitFor({ timeout: 10_000 });
        await expect(page.locator('.preventPwdChangeHolder + .forcePwdChangeHolder input')).not.toBeChecked();
        await page.locator('.preventPwdChangeHolder + .forcePwdChangeHolder input').check();
        await expect(page.locator('.preventPwdChangeHolder + .forcePwdChangeHolder input')).toBeChecked();
        await page.locator('.editUserModal .btnSave').click();

        await logout(page);
        await page.locator('#loginLink').click();

        await expect(page.locator('h1')).toContainText('Login');
        await page.locator('#username').fill('testadmin@example.org');
        await page.locator('#passwordInput').fill('testadmin');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

        await expect(page.locator('.forcedPwdForm')).toBeVisible();
        await expect(page.locator('.alert-danger')).toHaveCount(0);
        await page.locator("input[name='pwd']").fill('MyNewPassword');
        await page.locator("input[name='pwd2']").fill('MyIncorrectPassword');
        await page.locator('.forcedPwdForm [name="change"]').click();

        await expect(page.locator('.forcedPwdForm')).toBeVisible();
        await expect(page.locator('.alert-danger')).toBeVisible();
        await page.locator("input[name='pwd']").fill('MyNewPassword');
        await page.locator("input[name='pwd2']").fill('MyNewPassword');
        await page.locator('.forcedPwdForm [name="change"]').click();

        await expect(page.locator('.alert-success')).toBeVisible();

        await logout(page);
        await page.locator('#loginLink').click();

        await expect(page.locator('h1')).toContainText('Login');
        await page.locator('#username').fill('testadmin@example.org');
        await page.locator('#passwordInput').fill('MyNewPassword');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

        await expect(page.locator('.forcedPwdForm')).toHaveCount(0);
        await expect(page.locator('.alert-success')).toBeVisible();
    });
});