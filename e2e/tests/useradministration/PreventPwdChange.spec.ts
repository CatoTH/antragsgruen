import { test, expect } from '../../fixtures';
import { loginAsGlobalAdmin, loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/BasePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';

test.describe('Useradmin: PreventPwdChange', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('prevent password change', async ({ page }) => {
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
        await expect(page.locator('.preventPwdChangeHolder input')).not.toBeChecked();
        await page.evaluate(() => {
            const chkbox = document.querySelector('.preventPwdChangeHolder input') as HTMLInputElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            chkbox.dispatchEvent(evt);
        });
        await expect(page.locator('.preventPwdChangeHolder input')).toBeChecked();
        await page.evaluate(() => {
            const btn = document.querySelector('.editUserModal .btnSave') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });

        await logout(page);
        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('#username').fill('testadmin@example.org');
        await page.locator('#passwordInput').fill('testadmin');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

        await page.locator('#myAccountLink').click();
        await expect(page.locator('#userPwd')).toHaveCount(0);
        await expect(page.locator('#userPwd2')).toHaveCount(0);
        await expect(page.locator('#nameFamily')).toBeVisible();

        await logout(page);

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
        await expect(page.locator('.preventPwdChangeHolder input')).toBeChecked();
        await page.evaluate(() => {
            const chkbox = document.querySelector('.preventPwdChangeHolder input') as HTMLInputElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            chkbox.dispatchEvent(evt);
        });
        await expect(page.locator('.preventPwdChangeHolder input')).not.toBeChecked();
        await page.evaluate(() => {
            const btn = document.querySelector('.editUserModal .btnSave') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });

        await logout(page);
        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('#username').fill('testadmin@example.org');
        await page.locator('#passwordInput').fill('testadmin');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();

        await page.locator('#myAccountLink').click();
        await expect(page.locator('#userPwd')).toBeVisible();
        await expect(page.locator('#userPwd2')).toBeVisible();
        await expect(page.locator('#nameFamily')).toBeVisible();
    });
});