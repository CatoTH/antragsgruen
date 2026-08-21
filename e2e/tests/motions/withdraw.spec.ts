import { test, expect } from '../../fixtures';
import { loginAsStdUser } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/BasePage';
import { MotionPage } from '../../pages/MotionPage';

const MOTION_SLUG = '321-o-zapft-is';

test.describe('Withdraw a motion', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('an initiator can withdraw their own motion', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdUser(page);

        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });

        await page.locator('.sidebarActions .withdraw a').click();
        await expect(page.locator('body')).toContainText(
            'Willst du diesen Antrag wirklich zurückziehen?',
        );
        await page.locator('.withdrawForm [name="withdraw"]').click();

        await expect(page.locator('body')).toContainText('Der Antrag wurde zurückgezogen.');
        await expect(page.locator('.motionDataTable .statusRow')).toContainText('Zurückgezogen');
        await expect(page.locator('.sidebarActions .withdraw a')).toHaveCount(0);

        await home.open();
        await expect(page.locator('.motionRow2.withdrawn')).toBeVisible();
    });
});
