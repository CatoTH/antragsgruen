import { test, expect } from '../../fixtures';
import { loginAsStdUser, logout } from '../../utils/auth';
import { FIRST_FREE_MOTION_ID } from '../../utils/constants';
import { ConsultationHomePage } from '../../pages/BasePage';
import { MotionEditPage } from '../../pages/MotionEditPage';

test.describe('Draft motion edit permissions', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('a draft created logged out can be edited', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        const createPage = await home.gotoMotionCreatePage();
        await createPage.fillInValidSampleData('Testantrag 1');
        await createPage.saveForm();
        await expect(page.locator('h1')).toContainText(/antrag bestätigen/i);
        await home.open();

        const editPage = new MotionEditPage(page);
        await editPage.open({ motionSlug: String(FIRST_FREE_MOTION_ID) });
        await expect(page.locator('h1')).toContainText('Antrag stellen');
        await expect(page.locator('[name="sections[1]"]')).toHaveValue('Testantrag 1');
    });

    test('a draft created logged in cannot be edited after logging out', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        const createPage = await home.gotoMotionCreatePage();
        await loginAsStdUser(page);
        await createPage.fillInValidSampleData('Testantrag 2');
        await createPage.saveForm();
        await expect(page.locator('h1')).toContainText(/antrag bestätigen/i);
        await home.open();

        const editPage = new MotionEditPage(page);
        await editPage.open({ motionSlug: String(FIRST_FREE_MOTION_ID) });
        await expect(page.locator('h1')).toContainText('Antrag stellen');
        await expect(page.locator('[name="sections[1]"]')).toHaveValue('Testantrag 2');

        await home.open();
        await logout(page);
        await editPage.open({ motionSlug: String(FIRST_FREE_MOTION_ID) });
        await expect(page.locator('h1')).not.toContainText('Antrag stellen');
        await expect(page.locator('[name="sections[1]"]')).toHaveCount(0);
    });
});
