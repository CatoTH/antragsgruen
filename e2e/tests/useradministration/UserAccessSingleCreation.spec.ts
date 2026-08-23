import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';
import { FIRST_FREE_USER_ID } from '../../utils/constants';

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
        await page.locator('.managedUserAccounts input').check();
        await consultationPage.saveForm();

        await new AdminIndexPage(page).open();
        await page.locator('.siteUsers').click();

        await page.locator('.addSingleInit .inputEmail').fill('blibla@example.org');
        await page.locator('.addUsersOpener.singleuser').click();
        await expect(page.locator('.addUsersByLogin.singleuser .showIfExists')).toHaveCount(0);
        await expect(page.locator('.addUsersByLogin.singleuser .showIfNew')).toBeVisible();
        await page.locator('#addSingleNameGiven').fill('Bli');
        await page.locator('#addSingleNameFamily').fill('Bla');
        await page.locator('#addSingleOrganization').fill('Blubb Ltd.');
        await expect(page.locator('#addUserPassword')).toHaveCount(0);
        await page.locator('#addSingleGeneratePassword').click();
        await expect(page.locator('#addUserPassword')).toBeVisible();
        await page.locator('#addUserPassword').fill('mypassword');
        await page.locator('.addUsersByLogin.singleuser .userGroup3').check();
        await page.locator('.addUsersByLogin.singleuser .userGroup4').uncheck();
        await expect(page.locator('#addSingleSendEmail')).toBeChecked();
        await page.locator('.addUsersByLogin.singleuser [name="addUsers"]').click();

        await expect(page.locator(`.user${FIRST_FREE_USER_ID}`)).toContainText('Bli Bla');
        await expect(page.locator(`.user${FIRST_FREE_USER_ID}`)).toContainText(
            'blibla@example.org, Blubb Ltd.',
        );
        await expect(page.locator(`.user${FIRST_FREE_USER_ID}`)).toContainText('Antragskommission');

        await new ConsultationHomePage(page).open();
        await logout(page);
        await page.locator('#loginLink').click();
        await page.locator('#username').fill('blibla@example.org');
        await page.locator('#passwordInput').fill('mypassword');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await expect(page.locator('.alert-success')).toContainText('Willkommen!');
        await expect(page.locator('#motionListLink')).toBeVisible();
        await page.locator('#motionListLink').click();
        await expect(page.locator('.motionListForm')).toBeVisible();
    });
});