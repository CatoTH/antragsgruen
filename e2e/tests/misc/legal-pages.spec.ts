import { test, expect } from '../../fixtures';

test.describe('Misc: legal pages', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('imprint and privacy pages are visible', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');

        await page.locator('#legalLink').click();
        await expect(page.locator('h1')).toContainText('Impressum');

        await page.locator('#privacyLink').click();
        await expect(page.locator('h1')).toContainText(/datenschutzerklärung/i);
        await expect(page.locator('body')).toContainText('None of your data are belong to us');
    });
});
