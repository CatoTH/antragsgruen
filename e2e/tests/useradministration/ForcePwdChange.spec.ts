import { test, expect } from '../../fixtures';
import { loginAsGlobalAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { dispatchClick } from '../../utils/dom';

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
        await test.step('be able to enforce password-change as admin', async () => {
            await dispatchClick(page, '.user1 .btnEdit');
            await page.locator('.editUserModal.in').waitFor({ timeout: 10_000 });
            await expect(page.locator('.preventPwdChangeHolder + .forcePwdChangeHolder input')).not.toBeChecked();
            await page.locator('.preventPwdChangeHolder + .forcePwdChangeHolder input').first().check();
            await expect(page.locator('.preventPwdChangeHolder + .forcePwdChangeHolder input')).toBeChecked();
            await dispatchClick(page, '.editUserModal .btnSave');

            await logout(page);
        });

        await test.step('be forced to set up a second factor', async () => {
            await page.locator('#loginLink').click();

            await expect(page.locator('h1')).toContainText('Login');
            await page.locator('#username').first().fill('testadmin@example.org');
            await page.locator('#passwordInput').first().fill('testadmin');
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

            await expect(page.locator('.forcedPwdForm').first()).toBeVisible();
            await expect(page.locator('.alert-danger').filter({ visible: true })).toHaveCount(0);
            await page.locator("input[name='pwd']").first().fill('MyNewPassword');
            await page.locator("input[name='pwd2']").first().fill('MyIncorrectPassword');
            await page.locator('.forcedPwdForm [name="change"]').click();

            await expect(page.locator('.forcedPwdForm').first()).toBeVisible();
            await expect(page.locator('.alert-danger').first()).toBeVisible();
            await page.locator("input[name='pwd']").first().fill('MyNewPassword');
            await page.locator("input[name='pwd2']").first().fill('MyNewPassword');
            await page.locator('.forcedPwdForm [name="change"]').click();

            await expect(page.locator('.alert-success').first()).toBeVisible();

            await logout(page);
        });

        await test.step('not have to do this again on the next login', async () => {
            await page.locator('#loginLink').click();

            await expect(page.locator('h1')).toContainText('Login');
            await page.locator('#username').first().fill('testadmin@example.org');
            await page.locator('#passwordInput').first().fill('MyNewPassword');
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

            await expect(page.locator('.forcedPwdForm').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.alert-success').first()).toBeVisible();
        });
    });
});