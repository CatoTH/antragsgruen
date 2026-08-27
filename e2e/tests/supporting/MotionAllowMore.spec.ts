import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';

test.describe('Supporting: MotionAllowMore', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('disable allowing more supporters', async ({ page }) => {
        await new ConsultationHomePage(page).open({ subdomain: 'bdk', consultationPath: 'bdk' });
        await loginAsStdAdmin(page);
        await test.step('check that allowing more supporters is enabled', async () => {
            await page.locator('.createMotion').click();
            await expect(page.locator('.supporterDataHead')).toContainText(/UNTERSTÜTZER\*INNEN/);
            await expect(page.locator('.supporterData .adderRow').first()).toBeVisible();

            await page.locator('#adminLink').click();
            await page.locator('.motionType7').click();
            await expect(page.locator('#typeAllowMoreSupporters')).toBeChecked();

            await page.locator('#typeAllowMoreSupporters').first().uncheck();
            await page.locator('.adminTypeForm [name="save"]').click();
            await expect(page.locator('#typeAllowMoreSupporters')).not.toBeChecked();

            await new ConsultationHomePage(page).open({ subdomain: 'bdk', consultationPath: 'bdk' });
            await page.locator('.createMotion').click();
            await expect(page.locator('.supporterDataHead')).toContainText(/UNTERSTÜTZER\*INNEN/);
            await expect(page.locator('.supporterData .adderRow').filter({ visible: true })).toHaveCount(0);
        });
    });
});