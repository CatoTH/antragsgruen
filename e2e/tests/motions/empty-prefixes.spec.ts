import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminMotionListPage } from '../../pages/AdminMotionListPage';

test.describe('Empty motion prefixes', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('motions stay visible after removing all prefixes', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);

        const motionList = new AdminMotionListPage(page);
        for (const motionId of [2, 3, 58]) {
            await motionList.open();
            await page.locator(`.adminMotionTable .motion${motionId} .titleCol a`).click();
            await page.locator('#motionTitlePrefix').first().fill('');
            await page.locator('#motionUpdateForm [name="save"]').click();
        }

        await home.open();
        await test.step('check that all motions are still visible', async () => {
            await expect(page.locator('.motionLink2').first()).toBeVisible();
            await expect(page.locator('.motionLink3').first()).toBeVisible();
            await expect(page.locator('.motionLink58').first()).toBeVisible();
        });

    });
});
