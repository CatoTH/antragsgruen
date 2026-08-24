import { test, expect } from '../../fixtures';
import { loginAsGlobalAdmin, loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';

test.describe('Useradmin: PreventPwdChange', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('prevent password change', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsGlobalAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/admin');
        await page.locator('.siteUsers').click();
        await page.locator('.user1').waitFor({ timeout: 10_000 });
        await page.locator('.user1 .btnEdit').click();
        await page.locator('.editUserModal.in').waitFor({ timeout: 10_000 });
        await expect(page.locator('.preventPwdChangeHolder input')).not.toBeChecked();
        await page.locator('.preventPwdChangeHolder input').check();
        await expect(page.locator('.preventPwdChangeHolder input')).toBeChecked();
        await page.locator('.editUserModal .btnSave').click();
        await page.waitForLoadState('networkidle');

        await logout(page);
        await loginAsStdAdmin(page);

        await page.locator('#myAccountLink').click();
        await expect(page.locator('#userPwd')).toHaveCount(0);
        await expect(page.locator('#userPwd2')).toHaveCount(0);
        await expect(page.locator('#nameFamily')).toBeVisible();

        await logout(page);

        await page.goto('/stdparteitag/std-parteitag/admin');
        await page.locator('#username').fill('globaladmin@example.org');
        await page.locator('#passwordInput').fill('testadmin');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await page.locator('.siteUsers').click();
        await page.locator('.user1').waitFor({ timeout: 10_000 });
        await page.locator('.user1 .btnEdit').click();
        await page.locator('.editUserModal.in').waitFor({ timeout: 10_000 });
        await expect(page.locator('.preventPwdChangeHolder input')).toBeChecked();
        await page.locator('.preventPwdChangeHolder input').uncheck();
        await expect(page.locator('.preventPwdChangeHolder input')).not.toBeChecked();
        await page.locator('.editUserModal .btnSave').click();
        await page.waitForLoadState('networkidle');

        await logout(page);
        await loginAsStdAdmin(page);

        await page.locator('#myAccountLink').click();
        await expect(page.locator('#userPwd')).toBeVisible();
        await expect(page.locator('#userPwd2')).toBeVisible();
        await expect(page.locator('#nameFamily')).toBeVisible();
    });
});