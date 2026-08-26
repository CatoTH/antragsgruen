import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';
import { FIRST_FREE_USER_ID } from '../../utils/constants';
import { dispatchClick } from '../../utils/dom';

test.describe('Useradmin: UserAccessSingleCreation', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create single user with proposed procedure permissions', async ({ page }) => {
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
        });

        await test.step('Create a dummy user with proposed procedure permissions', async () => {
            await page.locator('.addSingleInit .inputEmail').first().fill('blibla@example.org');
            await dispatchClick(page, '.addUsersOpener.singleuser');
            await expect(page.locator('.addUsersByLogin.singleuser .showIfExists').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.addUsersByLogin.singleuser .showIfNew').first()).toBeVisible();
            await page.locator('#addSingleNameGiven').first().fill('Bli');
            await page.locator('#addSingleNameFamily').first().fill('Bla');
            await page.locator('#addSingleOrganization').first().fill('Blubb Ltd.');
            await expect(page.locator('#addUserPassword').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '#addSingleGeneratePassword');
            await expect(page.locator('#addUserPassword').first()).toBeVisible();
            await page.locator('#addUserPassword').first().fill('mypassword');
            await page.locator('.addUsersByLogin.singleuser .userGroup3').first().check();
            await page.locator('.addUsersByLogin.singleuser .userGroup4').first().uncheck();
            await expect(page.locator('#addSingleSendEmail')).toBeChecked();
            await page.locator('.addUsersByLogin.singleuser [name="addUsers"]').click();

            await expect(page.locator(`.user${FIRST_FREE_USER_ID}`)).toContainText('Bli Bla');
            await expect(page.locator(`.user${FIRST_FREE_USER_ID}`)).toContainText(
                'blibla@example.org, Blubb Ltd.',
            );
            await expect(page.locator(`.user${FIRST_FREE_USER_ID}`)).toContainText('Antragskommission');

            await new ConsultationHomePage(page).open();
            await logout(page);
        });

        await test.step('log in with the new user', async () => {
            await page.locator('#loginLink').click();
            await page.locator('#username').first().fill('blibla@example.org');
            await page.locator('#passwordInput').first().fill('mypassword');
            await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
            await expect(page.locator('.alert-success')).toContainText('Willkommen!');
            await expect(page.locator('#motionListLink').first()).toBeVisible();
            await page.locator('#motionListLink').click();
            await expect(page.locator('.motionListForm').first()).toBeVisible();
        });
    });
});