import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { setConfig } from '../../utils/test-api';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';
import { dispatchClick } from '../../utils/dom';

test.describe('Useradmin: UserAccessBatchCreationNoEmail', () => {
    test.beforeEach(async ({ db, request }) => {
        await db.populate('dbdata1');
    });

    test('batch creation with mail disabled', async ({ page, request }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        const consultationPage = new AdminConsultationPage(page);
        await consultationPage.open();
        await test.step('check the basic configuration', async () => {
            await page.locator('.managedUserAccounts input').first().check();
            await consultationPage.saveForm();

            await new AdminIndexPage(page).open();
            await page.locator('.siteUsers').click();

            await dispatchClick(page, '.addUsersOpener.email');
            await expect(page.locator('.alert-info')).toContainText('Benachrichtigungs-E-Mail');
            await expect(page.locator('.alert-info').getByText('Datenschutzgründen').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#passwords').filter({ visible: true })).toHaveCount(0);

            await setConfig(request, { mailService: { transport: 'none' } });

            await new AdminIndexPage(page).open();
            await page.locator('.siteUsers').click();
        });

        await test.step('disable e-mails', async () => {
            await expect(page.locator('#emailAddresses').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '.addUsersOpener.email');
            await expect(page.locator('.alert-info').getByText('Benachrichtigungs-E-Mail').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.alert-info')).toContainText('Datenschutzgründen');
        });

        await test.step('create a user using the old batch-creation mode', async () => {
            await expect(page.locator('#emailAddresses').first()).toBeVisible();
            await expect(page.locator('#passwords').first()).toBeVisible();

            await page.locator('#emailAddresses').first().fill('blibla@example.org');
            await page.locator('#passwords').first().fill('bliblablubb');
            await page.locator('#names').first().fill('Kasper');
            await page.locator('.addUsersByLogin.multiuser [name="addUsers"]').click();

            await expect(page.locator('.userAdminList')).toContainText('Kasper');
            await expect(page.locator('.userAdminList')).toContainText('blibla@example.org');

            await new ConsultationHomePage(page).open();
            await logout(page);
        });

        await test.step('log in with the new user', async () => {
            await page.locator('#loginLink').click();
            await page.locator('#username').first().fill('blibla@example.org');
            await page.locator('#passwordInput').first().fill('bliblablubb');
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
            await expect(page.locator('.alert-success')).toContainText('Willkommen!');
        });
    });
});