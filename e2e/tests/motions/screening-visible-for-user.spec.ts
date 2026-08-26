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
        await test.step('activate screening', async () => {
            await page.locator('#screeningMotions').first().check();
            await page.locator('.adminTypeForm [name="save"]').first().click();

            await home.open();
            await logout(page);
            await loginAsStdUser(page);

            const createPage = await home.gotoMotionCreatePage();
            await createPage.createMotion('Unscreened motion', true);
            await home.open();
        });

        await test.step('create a motion', async () => {
            await expect(page.locator('.motionListStd').first()).toBeVisible();
        });

        await test.step('check that other users don\\\'t see it', async () => {
            await expect(page.locator('.motionListStd').getByText('Unscreened motion').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.myMotionList')).toContainText('Unscreened motion');

            await logout(page);
            await home.open();
            await expect(page.locator('.myMotionList').getByText('Unscreened motion').filter({ visible: true })).toHaveCount(0);

            await loginAsStdAdmin(page);
            await home.open();
            await expect(page.locator('.myMotionList').getByText('Unscreened motion').filter({ visible: true })).toHaveCount(0);
        });
    });
});
