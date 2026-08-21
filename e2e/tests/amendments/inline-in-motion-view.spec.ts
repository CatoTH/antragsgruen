import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/BasePage';

test.describe('Amendments: InlineInMotionView', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('check that AE2 is working correctly', async ({ page }) => {
        await new ConsultationHomePage(page).gotoMotionView(2);

        await expect(page.locator('body')).not.toContainText('Neuer Punkt');
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
        await expect(page.locator('body')).not.toContainText('Neuer Punkt');
        await expect(page.locator('body')).toContainText('Auffi Gamsbart nimma');

        await expect(page.locator('body')).toContainText('Woibbadinga noch da Giasinga');
        await expect(page.locator('.deleted')).not.toContainText('Woibbadinga noch da Giasinga');
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
        await expect(page.locator('.deleted')).not.toContainText('Woibbadinga noch da Giasinga');
    });
});