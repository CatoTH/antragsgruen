import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';
import { FIRST_FREE_USER_ID } from '../../utils/constants';

const USERNAME = 'testaccount@example.org';
const PASSWORD = 'testpassword';

test.describe('Useradmin: AccountScreening', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('screen account access requests', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        const consultationPage = new AdminConsultationPage(page);
        await consultationPage.open();
        await page.locator('.forceLogin input').check();
        await page.locator('.managedUserAccounts input').check();
        await consultationPage.saveForm();
        await logout(page);

        await new ConsultationHomePage(page).open();
        await expect(page.locator('.managedAccountHint')).not.toBeVisible();
        await page.locator('#createAccount').check();
        await expect(page.locator('.managedAccountHint')).toBeVisible();

        await page.locator('#username').fill(USERNAME);
        await page.locator('#name').fill('Tester');
        await page.locator('#passwordInput').fill(PASSWORD);
        await page.locator('#passwordConfirm').fill(PASSWORD);
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await expect(page.locator('h1')).toContainText(/zugang bestätigen/i);

        await page.locator('#code').fill('testCode');
        await page.locator('#confirmAccountForm [type="submit"]').click();
        await expect(page.locator('h1')).toContainText(/zugang bestätigt/i);
        await expect(page.locator('.confirmedScreeningMsg')).toBeVisible();
        await expect(page.locator('body')).toContainText('E-Mail sent to: testadmin@example.org');

        await new ConsultationHomePage(page).open();
        await expect(page.locator('.noAccessAlert')).toBeVisible();
        await expect(page.locator('.askPermissionForm')).toHaveCount(0);
        await expect(page.locator('.askedForPermissionAlert')).toBeVisible();
        await logout(page);

        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();
        await expect(page.locator('.userAdminList')).not.toContainText('testaccount@example.org');
        await expect(page.locator('#accountsScreenForm')).toContainText('testaccount@example.org');
        await page.locator(`#screenUser${FIRST_FREE_USER_ID}`).check();
        await page.locator('#accountsScreenForm [name="noAccess"]').click();
        await expect(page.locator('.userAdminList')).not.toContainText('testaccount@example.org');
        await expect(page.locator('#accountsScreenForm')).not.toContainText('testaccount@example.org');
        await new ConsultationHomePage(page).open();
        await logout(page);

        await page.locator('#loginLink').click();
        await page.locator('#username').fill(USERNAME);
        await page.locator('#passwordInput').fill(PASSWORD);
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await expect(page.locator('.noAccessAlert')).toBeVisible();
        await expect(page.locator('.askPermissionForm')).toBeVisible();
        await expect(page.locator('.askedForPermissionAlert')).toHaveCount(0);
        await page.locator('.askPermissionForm [name="askPermission"]').click();
        await expect(page.locator('body')).toContainText('E-Mail sent to: testadmin@example.org');
        await expect(page.locator('.noAccessAlert')).toBeVisible();
        await expect(page.locator('.askPermissionForm')).toHaveCount(0);
        await expect(page.locator('.askedForPermissionAlert')).toBeVisible();
        await logout(page);

        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();
        await expect(page.locator('.userAdminList')).not.toContainText('testaccount@example.org');
        await expect(page.locator('#accountsScreenForm')).toContainText('testaccount@example.org');
        await page.locator(`#screenUser${FIRST_FREE_USER_ID}`).check();
        await page.locator('#accountsScreenForm [name="grantAccess"]').click();
        await expect(page.locator('.userAdminList')).toContainText('testaccount@example.org');
        await expect(page.locator('#accountsScreenForm')).not.toContainText('testaccount@example.org');
        await new ConsultationHomePage(page).open();
        await logout(page);

        await page.locator('#loginLink').click();
        await page.locator('#username').fill(USERNAME);
        await page.locator('#passwordInput').fill(PASSWORD);
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await expect(page.locator('.noAccessAlert')).toHaveCount(0);
        await expect(page.locator('.askPermissionForm')).toHaveCount(0);
        await expect(page.locator('.askedForPermissionAlert')).toHaveCount(0);
        await expect(page.locator('.createMotion')).toBeVisible();
        await expect(page.locator('.motionLink2')).toBeVisible();
    });
});