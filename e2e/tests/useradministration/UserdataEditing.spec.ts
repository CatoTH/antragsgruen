import { test, expect } from '../../fixtures';
import { loginAsGlobalAdmin, loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';

test.describe('Useradmin: UserdataEditing', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('edit user data as global admin', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();

        await expect(page.locator('.editUserModal')).toHaveCount(0);
        await page.locator('.user7 .btnEdit').click();
        await expect(page.locator('.editUserModal .onlyGlobalAdminsHint')).toBeVisible();
        await expect(page.locator('.editUserModal .inputNameGiven')).toHaveCount(0);
        await expect(page.locator('.editUserModal .inputNameFamily')).toHaveCount(0);
        await expect(page.locator('.editUserModal .inputOrganization')).toHaveCount(0);
        await page.locator('.editUserModal .btnCancel').click();
        await logout(page);

        await page.goto('/stdparteitag/std-parteitag/admin');
        await page.locator('#username').fill('globaladmin@example.org');
        await page.locator('#passwordInput').fill('testadmin');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await page.locator('.siteUsers').click();
        await page.locator('.user7 .btnEdit').click();
        await expect(page.locator('.editUserModal .onlyGlobalAdminsHint')).toHaveCount(0);
        await expect(page.locator('.editUserModal .inputNameGiven')).toBeVisible();
        await expect(page.locator('.editUserModal .inputNameFamily')).toBeVisible();
        await expect(page.locator('.editUserModal .inputOrganization')).toBeVisible();

        await page.locator('.editUserModal .inputNameGiven').fill('Sincon');
        await page.locator('.editUserModal .inputNameFamily').fill('Anö');
        await page.locator('.editUserModal .inputOrganization').fill('Testorga');
        await page.locator('.editUserModal .btnSave').click();

        await expect(page.locator('.user7')).toContainText('Sincon Anö');
        await expect(page.locator('.user7')).toContainText('Testorga');

        await page.locator('.user7 .btnEdit').click();
        await expect(page.locator('.editUserModal .inputPassword')).toHaveCount(0);
        await page.locator('.editUserModal .btnSetPwdOpener').click();
        await expect(page.locator('.editUserModal .inputPassword')).toBeVisible();
        await page.locator('.editUserModal .inputPassword').fill('GreatSecretPassword');
        await page.locator('.editUserModal .btnSave').click();

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