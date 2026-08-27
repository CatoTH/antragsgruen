import { test, expect } from '../../fixtures';
import { loginAsGlobalAdmin, loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { dispatchClick } from '../../utils/dom';

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
        await test.step('be able to restrict password changing as super-admin', async () => {
            await dispatchClick(page, '.user1 .btnEdit');
            await page.locator('.editUserModal.in').waitFor({ timeout: 10_000 });
            await expect(page.locator('.preventPwdChangeHolder input')).not.toBeChecked();
            await page.locator('.preventPwdChangeHolder input').first().check();
            await expect(page.locator('.preventPwdChangeHolder input')).toBeChecked();
            await dispatchClick(page, '.editUserModal .btnSave');
            await page.waitForLoadState('networkidle');

            await logout(page);
            await loginAsStdAdmin(page);
        });

        await test.step('not see the password option anymore', async () => {
            await page.locator('#myAccountLink').click();
            await expect(page.locator('#userPwd').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#userPwd2').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#nameFamily').first()).toBeVisible();

            await logout(page);

            await page.goto('/stdparteitag/std-parteitag/admin');
            await page.locator('#username').first().fill('globaladmin@example.org');
            await page.locator('#passwordInput').first().fill('testadmin');
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
            await page.locator('.siteUsers').click();
            await page.locator('.user1').waitFor({ timeout: 10_000 });
        });

        await test.step('allow it again', async () => {
            await dispatchClick(page, '.user1 .btnEdit');
            await page.locator('.editUserModal.in').waitFor({ timeout: 10_000 });
            await expect(page.locator('.preventPwdChangeHolder input')).toBeChecked();
            await page.locator('.preventPwdChangeHolder input').first().uncheck();
            await expect(page.locator('.preventPwdChangeHolder input')).not.toBeChecked();
            await dispatchClick(page, '.editUserModal .btnSave');
            await page.waitForLoadState('networkidle');

            await logout(page);
            await loginAsStdAdmin(page);
        });

        await test.step('see the password option again', async () => {
            await page.locator('#myAccountLink').click();
            await expect(page.locator('#userPwd').first()).toBeVisible();
            await expect(page.locator('#userPwd2').first()).toBeVisible();
            await expect(page.locator('#nameFamily').first()).toBeVisible();
        });
    });
});