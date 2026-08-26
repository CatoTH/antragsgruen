import { test, expect } from '../../fixtures';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionListPage } from '../../pages/AdminMotionListPage';
import { AdminAppearancePage } from '../../pages/AdminAppearancePage';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('DraftsNotVisible', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('set the motion to draft state and make sure it is not visible in any layout', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await expect(page.locator('body')).toContainText('A4');
        await expect(page.locator('body')).toContainText('Testantrag');

        const motionList = new AdminMotionListPage(page);
        await new AdminIndexPage(page).open();
        await motionList.open();
        await page.locator('.adminMotionTable .motion58 .titleCol a').first().click();
        await test.step('set the motion to draft state and make sure it\\\' not visible', async () => {
            await page.locator('#motionStatus').first().selectOption('1');
            await page.locator('#motionUpdateForm [name="save"]').click();

            const layoutIds = await page.evaluate(() => {
                const w = window as any;
                const result: string[] = [];
                const list = w.ConsultationStartLayouts || {};
                for (const k of Object.keys(list)) result.push(k);
                return result.length > 0
                    ? result
                    : ['0', '1', '2', '3', '4', '5'];
            });

            for (const layoutId of layoutIds) {
                const appearance = new AdminAppearancePage(page);
                await new AdminIndexPage(page).open();
                await appearance.open();
                await page.locator('#startLayoutType').first().selectOption(layoutId);
                await appearance.saveForm();

                await new ConsultationHomePage(page).open();
                await expect(page.locator('body')).not.toContainText('A4', { useInnerText: true });
                await expect(page.locator('body')).not.toContainText('Testantrag', { useInnerText: true });
            }
        });
    });
});