import { test, expect } from '../../fixtures';
import { logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';

test.describe('Useradmin: ForcePwdChange', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('force password change', async ({ page }) => {
        await new ConsultationHomePage(page).open();

        await page.goto('/stdparteitag/std-parteitag/admin/index');
        await page.locator('#username').fill('globaladmin@example.org');
        await page.locator('#passwordInput').fill('testadmin');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await page.locator('.siteUsers').click();
        await page.evaluate(() => {
            const btn = document.querySelector('.user1 .btnEdit') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.forcePwdChangeHolder input')).not.toBeChecked();
        await page.evaluate(() => {
            const chkbox = document.querySelector('.forcePwdChangeHolder input') as HTMLInputElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            chkbox.dispatchEvent(evt);
        });
        await expect(page.locator('.forcePwdChangeHolder input')).toBeChecked();
        await page.evaluate(() => {
            const btn = document.querySelector('.editUserModal .btnSave') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });

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