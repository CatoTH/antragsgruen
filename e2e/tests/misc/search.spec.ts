import { test, expect } from '../../fixtures';

test.describe('Misc: search', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('search motions and amendments', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');

        await page.locator('#sidebar .query').fill('O’zapft');
        await page.locator('#sidebar .form-search [type="submit"]').click();

        await expect(page.locator('body')).toContainText('A2: O’zapft');
        await expect(page.locator('body')).not.toContainText('A3: Test');

        await page.locator('.motion2 a').click();
        await expect(page.locator('.breadcrumb')).toContainText('Suche');

        await page.goto('/stdparteitag/std-parteitag');

        await page.locator('#sidebar .query').fill('neuer absatz');
        await page.locator('#sidebar .form-search [type="submit"]').click();

        await expect(page.locator('body')).toContainText('Ä2 zu A2');

        await page.locator('.amendment3 a').click();
        await expect(page.locator('.breadcrumb')).toContainText('Suche');

        await page.goto('/stdparteitag/std-parteitag');

        await page.locator('#sidebar .query').fill('Lischke');
        await page.locator('#sidebar .form-search [type="submit"]').click();

        await expect(page.locator('body')).not.toContainText('Ä2 zu A2');
        await expect(page.locator('body')).toContainText('Ä4 zu A2');
    });
});
