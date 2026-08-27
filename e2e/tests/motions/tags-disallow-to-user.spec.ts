import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';

test.describe('Disallow tag selection for users', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('the tag selector disappears when users may not set tags', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionCreatePage();
        await expect(page.locator('#tagSelect').first()).toBeVisible();

        await home.open();
        await loginAsStdAdmin(page);

        const consultation = new AdminConsultationPage(page);
        await consultation.open();
        await test.step('disable tags for users', async () => {
            await expect(page.locator('#allowUsersToSetTags')).toBeChecked();
            await page.locator('#allowUsersToSetTags').first().uncheck();
            await consultation.saveForm();

            await home.open();
            await logout(page);

            const createPage = await home.gotoMotionCreatePage();
            await expect(page.locator('#tagSelect').filter({ visible: true })).toHaveCount(0);

            await createPage.fillInValidSampleData('Testantrag 1', false);
            await createPage.saveForm();
            await page.locator('#motionConfirmForm [name="confirm"]').click();
        });
    });
});
