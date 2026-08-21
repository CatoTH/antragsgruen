import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Exports: motion single HTML', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('single motion HTML from admin interface', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);

        await page.locator('#motionListLink').click();
        await expect(page.locator('h1')).toContainText(/liste: anträge/i);

        await page.locator('.adminMotionTable .motion3 a.plainHtml').click();
        await expect(page.locator('body')).toContainText(
            'Seltsame Zeichen: & % $ # _ { } ~ ^ \\ \\today',
        );
    });
});
