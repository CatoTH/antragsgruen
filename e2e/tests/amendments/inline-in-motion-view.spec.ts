import { test, expect } from '../../fixtures';
import { MotionPage } from '../../pages/MotionPage';

test.describe('Amendments: InlineInMotionView', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('check that AE2 is working correctly', async ({ page }) => {
        await new MotionPage(page).open({ motionSlug: 2 });

        await expect(page.locator('body')).not.toContainText('Neuer Punkt', { useInnerText: true });
        await expect(page.locator('body')).toContainText('Auffi Gamsbart nimma');
        await page.evaluate(() => {
            const w = window as any;
            const el = w.$('#section_2_1').find('ul.bookmarks .amendment3');
            el.mouseover();
        });
        await expect(page.locator('body')).toContainText('Neuer Punkt');
        await expect(page.locator('body')).toContainText('Auffi Gamsbart nimma');
        await page.evaluate(() => {
            const w = window as any;
            const el = w.$('#section_2_1').find('ul.bookmarks .amendment3');
            el.mouseout();
        });
        await expect(page.locator('body')).not.toContainText('Neuer Punkt', { useInnerText: true });
        await expect(page.locator('body')).toContainText('Auffi Gamsbart nimma');

        await expect(page.locator('body')).toContainText('Woibbadinga noch da Giasinga');
        await expect(page.locator('.deleted').getByText('Woibbadinga noch da Giasinga').filter({ visible: true })).toHaveCount(0);
        await page.evaluate(() => {
            const w = window as any;
            const el = w.$('#section_2_4').find('ul.bookmarks .amendment3');
            el.mouseover();
        });
        await expect(page.locator('.deleted')).toContainText('Woibbadinga noch da Giasinga');
        await page.evaluate(() => {
            const w = window as any;
            const el = w.$('#section_2_4').find('ul.bookmarks .amendment3');
            el.mouseout();
        });
        await expect(page.locator('.deleted').getByText('Woibbadinga noch da Giasinga').filter({ visible: true })).toHaveCount(0);
    });
});