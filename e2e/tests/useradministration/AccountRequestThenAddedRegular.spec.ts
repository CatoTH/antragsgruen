import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';
import { FIRST_FREE_USER_ID } from '../../utils/constants';
import { dispatchClick } from '../../utils/dom';

const USERNAME = 'testaccount@example.org';
const PASSWORD = 'testpassword';

test.describe('Useradmin: AccountRequestThenAddedRegular', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('request account then add as regular user', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        const consultationPage = new AdminConsultationPage(page);
        await consultationPage.open();
        await test.step('activate managed accounts', async () => {
            await page.locator('.forceLogin input').first().check();
            await page.locator('.managedUserAccounts input').first().check();
            await consultationPage.saveForm();
            await logout(page);

            await new ConsultationHomePage(page).open();
        });

        await test.step('create an account', async () => {
            await expect(page.locator('.managedAccountHint').filter({ visible: true })).toHaveCount(0);
            await page.locator('#createAccount').first().check();
            await expect(page.locator('.managedAccountHint').first()).toBeVisible();

            await page.locator('#username').first().fill(USERNAME);
            await page.locator('#name').first().fill('Tester');
            await page.locator('#passwordInput').first().fill(PASSWORD);
            await page.locator('#passwordConfirm').first().fill(PASSWORD);
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
            await expect(page.locator('h1')).toContainText(/zugang bestätigen/i);

            await page.locator('#code').first().fill('testCode');
            await page.locator('#confirmAccountForm [type="submit"]').click();
            await expect(page.locator('h1')).toContainText(/zugang bestätigt/i);
            await expect(page.locator('.confirmedScreeningMsg').first()).toBeVisible();
            await expect(page.locator('body')).toContainText('E-Mail sent to: testadmin@example.org');

            await new ConsultationHomePage(page).open();
            await expect(page.locator('.noAccessAlert').first()).toBeVisible();
            await expect(page.locator('.askPermissionForm').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.askedForPermissionAlert').first()).toBeVisible();
            await logout(page);

            await new ConsultationHomePage(page).open();
            await loginAsStdAdmin(page);
            await new AdminIndexPage(page).open();
            await page.locator('.siteUsers').click();
        });

        await test.step('Add the user through the regular addition process', async () => {
            await expect(page.locator('.userAdminList').getByText('testaccount@example.org').filter({ visible: true })).toHaveCount(0);
        });

        await test.step('not see them at the requester list anymore', async () => {
            await expect(page.locator('#accountsScreenForm')).toContainText('testaccount@example.org');

            await page.locator('.addSingleInit .inputEmail').first().fill(USERNAME);
            await dispatchClick(page, '.addUsersOpener.singleuser');

            await expect(page.locator('.addUsersByLogin.singleuser .showIfExists').first()).toBeVisible();
            await expect(page.locator('.addUsersByLogin.singleuser .showIfNew').filter({ visible: true })).toHaveCount(0);
            await page.locator('.addUsersByLogin.singleuser [name="addUsers"]').click();

            await expect(page.locator(`.user${FIRST_FREE_USER_ID}`)).toContainText('Tester');
            await expect(page.locator(`.user${FIRST_FREE_USER_ID}`)).toContainText('testaccount@example.org');
            await expect(page.locator(`.user${FIRST_FREE_USER_ID}`)).toContainText('Teilnehmer*in');

            await expect(page.locator('#accountsScreenForm').getByText('testaccount@example.org').filter({ visible: true })).toHaveCount(0);

            await new ConsultationHomePage(page).open();
            await logout(page);
        });

        await test.step('be able to see everything now', async () => {
            await page.locator('#loginLink').click();
            await page.locator('#username').first().fill(USERNAME);
            await page.locator('#passwordInput').first().fill(PASSWORD);
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
            await expect(page.locator('.noAccessAlert').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.askPermissionForm').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.askedForPermissionAlert').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.createMotion').first()).toBeVisible();
            await expect(page.locator('.motionLink2').first()).toBeVisible();
        });
    });
});