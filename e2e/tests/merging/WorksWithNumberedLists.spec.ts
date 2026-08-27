import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Merging: numbered lists', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('preserves numbered list start attribute', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);
        await page.goto('/stdparteitag/std-parteitag/motion/123-textformatierungen');
        await page.locator('#sidebar .mergeamendments a').click();
        await page.locator('.mergeAllRow [type="submit"]').click();
        await page.waitForTimeout(1000);

        const start = await page.evaluate(() => {
            const ol = document.querySelector('#sections_2_5_wysiwyg ol') as HTMLElement | null;
            return ol?.getAttribute('start') ?? null;
        });
        expect(start).toBe('4');

        await page.evaluate(() => {
            document.querySelectorAll('.none').forEach((el) => el.remove());
            document.querySelectorAll('#draftSavingPanel').forEach((el) => el.remove());
        });
        await page.waitForTimeout(1000);

        await page.locator('.motionMergeForm [name="save"]').click();

        const ols = await page.evaluate(() => {
            return document.querySelectorAll('.paragraph ol[start="4"]').length;
        });
        expect(ols).toBe(1);

        const text = await page.evaluate(() => {
            return (document.querySelector('.paragraph ol[start="4"]') as HTMLElement | null)?.textContent ?? '';
        });
        expect(text).toContain('Seltsame Zeichen');
    });
});
