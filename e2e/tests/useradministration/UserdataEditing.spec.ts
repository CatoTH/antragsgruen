import { test, expect } from '../../fixtures';
import { loginAsGlobalAdmin, loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { dispatchClick } from '../../utils/dom';

test.describe('Useradmin: UserdataEditing', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('edit user data as global admin', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();

        await test.step('not be able to edit users as regular admin', async () => {
            await expect(page.locator('.editUserModal').filter({ visible: true })).toHaveCount(0);
        });

        await test.step('be able to edit users as global admin', async () => {
            await dispatchClick(page, '.user7 .btnEdit');
            await expect(page.locator('.editUserModal .onlyGlobalAdminsHint').first()).toBeVisible();
            await expect(page.locator('.editUserModal .inputNameGiven').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.editUserModal .inputNameFamily').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.editUserModal .inputOrganization').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '.editUserModal .btnCancel');
            await logout(page);

            await page.goto('/stdparteitag/std-parteitag/admin');
            await page.locator('#username').first().fill('globaladmin@example.org');
            await page.locator('#passwordInput').first().fill('testadmin');
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
            await page.locator('.siteUsers').click();
        });

        await test.step('change their password', async () => {
            await dispatchClick(page, '.user7 .btnEdit');
            await expect(page.locator('.editUserModal .onlyGlobalAdminsHint').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.editUserModal .inputNameGiven').first()).toBeVisible();
            await expect(page.locator('.editUserModal .inputNameFamily').first()).toBeVisible();
            await expect(page.locator('.editUserModal .inputOrganization').first()).toBeVisible();

            await page.locator('.editUserModal .inputNameGiven').first().fill('Sincon');
            await page.locator('.editUserModal .inputNameFamily').first().fill('Anö');
            await page.locator('.editUserModal .inputOrganization').first().fill('Testorga');
            await dispatchClick(page, '.editUserModal .btnSave');
            await page.waitForLoadState('networkidle');

            await expect(page.locator('.user7')).toContainText('Sincon Anö');
            await expect(page.locator('.user7')).toContainText('Testorga');

            await dispatchClick(page, '.user7 .btnEdit');
            await expect(page.locator('.editUserModal .inputPassword').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '.editUserModal .btnSetPwdOpener');
            await expect(page.locator('.editUserModal .inputPassword').first()).toBeVisible();
            await page.locator('.editUserModal .inputPassword').first().fill('GreatSecretPassword');
            await dispatchClick(page, '.editUserModal .btnSave');
            await page.waitForLoadState('networkidle');

            await new ConsultationHomePage(page).open();
            await logout(page);
            await page.goto('/stdparteitag/std-parteitag');
            await page.locator('#username').first().fill('consultationadmin@example.org');
            await page.locator('#passwordInput').first().fill('consultationadmin');
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        });

        await test.step('confirm the changes as the user', async () => {
            await expect(page.locator('.passwordError').first()).toBeVisible();
            await page.locator('#passwordInput').first().fill('GreatSecretPassword');
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
            await page.locator('#myAccountLink').click();
            await expect(page.locator('.userAccountForm')).toContainText('Sincon');
            await expect(page.locator('.userAccountForm')).toContainText('Anö');
        });
    });
});