import { test, expect } from '../../fixtures';

test.describe('Misc: search', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('search motions and amendments', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');

        await test.step('search without enterig a term', async () => {
            await page.locator('#sidebar .query').first().fill('O’zapft');
            await page.locator('#sidebar .form-search [type="submit"]').click();

            await expect(page.locator('body')).toContainText('A2: O’zapft');
            await expect(page.locator('body')).not.toContainText('A3: Test', { useInnerText: true });

            await page.locator('.motion2 a').click();
            await expect(page.locator('.breadcrumb')).toContainText('Suche');

            await page.goto('/stdparteitag/std-parteitag');
        });

        await test.step('search a motion', async () => {
            await page.locator('#sidebar .query').first().fill('neuer absatz');
            await page.locator('#sidebar .form-search [type="submit"]').click();

            await expect(page.locator('body')).toContainText('Ä2 zu A2');

            await page.locator('.amendment3 a').click();
        });

        await test.step('check that the backlinks in the motions work', async () => {
            await expect(page.locator('.breadcrumb')).toContainText('Suche');

            await page.goto('/stdparteitag/std-parteitag');
        });

        await test.step('search an amendment', async () => {
            await page.locator('#sidebar .query').first().fill('Lischke');
        });

        await test.step('check that the backlinks in the amendments work', async () => {
            await page.locator('#sidebar .form-search [type="submit"]').click();
        });

        await test.step('search an amendment by proposer name', async () => {
            await expect(page.locator('body')).not.toContainText('Ä2 zu A2', { useInnerText: true });
            await expect(page.locator('body')).toContainText('Ä4 zu A2');
        });
    });
});
