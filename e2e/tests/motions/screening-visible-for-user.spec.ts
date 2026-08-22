import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';

test.describe('Unscreened motions visible for their initiator', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('an unscreened motion is only listed for its own initiator', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);

        const motionType = new AdminMotionTypePage(page);
        await motionType.open({ motionTypeId: 1 });
        await page.locator('#screeningMotions').check();
        await motionType.saveForm();

        await home.open();
        await logout(page);
        await loginAsStdUser(page);

        const createPage = await home.gotoMotionCreatePage();
        await createPage.createMotion('Unscreened motion', true);
        await home.open();

        await expect(page.locator('.motionListStd')).toBeVisible();
        await expect(page.locator('.motionListStd')).not.toContainText('Unscreened motion');
        await expect(page.locator('.myMotionList')).toContainText('Unscreened motion');

        await logout(page);
        await home.open();
        await expect(page.locator('.myMotionList')).not.toContainText('Unscreened motion');

        await loginAsStdAdmin(page);
        await home.open();
        await expect(page.locator('.myMotionList')).not.toContainText('Unscreened motion');
    });
});
