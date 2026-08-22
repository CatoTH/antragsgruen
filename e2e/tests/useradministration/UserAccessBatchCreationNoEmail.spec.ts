import { test, expect } from '../../fixtures';
import { logout } from '../../utils/auth';
import { setConfig } from '../../utils/test-api';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';

test.describe('Useradmin: UserAccessBatchCreationNoEmail', () => {
    test.beforeEach(async ({ db, request }) => {
        await db.populate('dbdata1');
    });

    test('batch creation with mail disabled', async ({ page, request }) => {
        await new AdminIndexPage(page).open();
        const consultationPage = new AdminConsultationPage(page);
        await consultationPage.open();
        await page.locator('.managedUserAccounts input').check();
        await consultationPage.saveForm();

        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();

        await page.evaluate(() => {
            const btn = document.querySelector('.addUsersOpener.email') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.alert-info')).toContainText('Benachrichtigungs-E-Mail');
        await expect(page.locator('.alert-info')).not.toContainText('Datenschutzgründen');
        await expect(page.locator('#passwords')).toHaveCount(0);

        await setConfig(request, { mailService: { transport: 'none' } });

        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();

        await expect(page.locator('#emailAddresses')).toHaveCount(0);
        await page.evaluate(() => {
            const btn = document.querySelector('.addUsersOpener.email') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.alert-info')).not.toContainText('Benachrichtigungs-E-Mail');
        await expect(page.locator('.alert-info')).toContainText('Datenschutzgründen');

        await expect(page.locator('#emailAddresses')).toBeVisible();
        await expect(page.locator('#passwords')).toBeVisible();

        await page.locator('#emailAddresses').fill('blibla@example.org');
        await page.locator('#passwords').fill('bliblablubb');
        await page.locator('#names').fill('Kasper');
        await page.locator('.addUsersByLogin.multiuser [name="addUsers"]').click();

        await expect(page.locator('.userAdminList')).toContainText('Kasper');
        await expect(page.locator('.userAdminList')).toContainText('blibla@example.org');

        await new ConsultationHomePage(page).open();
        await logout(page);
        await page.locator('#loginLink').click();
        await page.locator('#username').fill('blibla@example.org');
        await page.locator('#passwordInput').fill('bliblablubb');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await expect(page.locator('.alert-success')).toContainText('Willkommen!');
    });
});