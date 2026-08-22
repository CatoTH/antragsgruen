import { test, expect } from '../../fixtures';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { FIRST_FREE_CONSULTATION_ID } from '../../utils/constants';

test.describe('UsersCannotSeeConsultationsWithoutAccess', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('users only see consultations they have access to', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);

        await new AdminIndexPage(page).open();
        await page.locator('.siteConsultationsLink').click();
        await page.locator('#newTitle').fill('Test3');
        await page.locator('#newShort').fill('test3');
        await page.locator('#newPath').fill('test3');
        await page
            .locator('.consultationCreateForm [name="createConsultation"]')
            .click();
        await logout(page);

        await loginAsStdUser(page);
        await new ConsultationHomePage(page).open();
        await page.locator('#myAccountLink').click();
        await expect(
            page.locator('.notificationLinks .consultation1'),
        ).toBeVisible();
        await expect(
            page.locator(
                `.notificationLinks .consultation${FIRST_FREE_CONSULTATION_ID}`,
            ),
        ).toBeVisible();
        await logout(page);

        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        const consultationPage = new AdminConsultationPage(page);
        await consultationPage.open();
        await page.locator('.forceLogin input').check();
        await page.locator('.managedUserAccounts input').check();
        await consultationPage.saveForm();
        await logout(page);

        await loginAsStdUser(page);
        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('.noAccessAlert')).toBeVisible();
        await page.locator('#myAccountLink').click();
        await expect(
            page.locator('.notificationLinks .consultation1'),
        ).toHaveCount(0);
        await expect(
            page.locator(
                `.notificationLinks .consultation${FIRST_FREE_CONSULTATION_ID}`,
            ),
        ).toBeVisible();
    });
});