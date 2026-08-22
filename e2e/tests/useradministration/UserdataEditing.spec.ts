import { test, expect } from '../../fixtures';
import { loginAsGlobalAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';

test.describe('Useradmin: UserdataEditing', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('edit user data as global admin', async ({ page }) => {
        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();

        await expect(page.locator('.editUserModal')).toHaveCount(0);
        await page.evaluate(() => {
            const btn = document.querySelector('.user7 .btnEdit') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.editUserModal .onlyGlobalAdminsHint')).toBeVisible();
        await expect(page.locator('.editUserModal .inputNameGiven')).toHaveCount(0);
        await expect(page.locator('.editUserModal .inputNameFamily')).toHaveCount(0);
        await expect(page.locator('.editUserModal .inputOrganization')).toHaveCount(0);
        await page.evaluate(() => {
            const btn = document.querySelector('.editUserModal .btnCancel') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await logout(page);

        await page.goto('/stdparteitag/std-parteitag/admin/index');
        await page.locator('#username').fill('globaladmin@example.org');
        await page.locator('#passwordInput').fill('testadmin');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await page.locator('.siteUsers').click();
        await page.evaluate(() => {
            const btn = document.querySelector('.user7 .btnEdit') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.editUserModal .onlyGlobalAdminsHint')).toHaveCount(0);
        await expect(page.locator('.editUserModal .inputNameGiven')).toBeVisible();
        await expect(page.locator('.editUserModal .inputNameFamily')).toBeVisible();
        await expect(page.locator('.editUserModal .inputOrganization')).toBeVisible();

        await page.locator('.editUserModal .inputNameGiven').fill('Sincon');
        await page.locator('.editUserModal .inputNameFamily').fill('Anö');
        await page.locator('.editUserModal .inputOrganization').fill('Testorga');
        await page.evaluate(() => {
            const btn = document.querySelector('.editUserModal .btnSave') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });

        await expect(page.locator('.user7')).toContainText('Sincon Anö');
        await expect(page.locator('.user7')).toContainText('Testorga');

        await page.evaluate(() => {
            const btn = document.querySelector('.user7 .btnEdit') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.editUserModal .inputPassword')).toHaveCount(0);
        await page.evaluate(() => {
            const btn = document.querySelector('.editUserModal .btnSetPwdOpener') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.editUserModal .inputPassword')).toBeVisible();
        await page.locator('.editUserModal .inputPassword').fill('GreatSecretPassword');
        await page.evaluate(() => {
            const btn = document.querySelector('.editUserModal .btnSave') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });

        await new ConsultationHomePage(page).open();
        await logout(page);
        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('#username').fill('consultationadmin@example.org');
        await page.locator('#passwordInput').fill('consultationadmin');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await expect(page.locator('.passwordError')).toBeVisible();
        await page.locator('#passwordInput').fill('GreatSecretPassword');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await page.locator('#myAccountLink').click();
        await expect(page.locator('.userAccountForm')).toContainText('Sincon');
        await expect(page.locator('.userAccountForm')).toContainText('Anö');
    });
});