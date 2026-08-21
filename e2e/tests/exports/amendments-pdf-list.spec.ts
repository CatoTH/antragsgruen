import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Exports: amendments PDF list', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('open amendments PDF list and download', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);

        await page.locator('#motionListLink').click();
        await expect(page.locator('h1')).toContainText(/liste: anträge/i);

        await page.locator('#exportAmendmentsBtn').click();
        await page.locator('.amendmentPdfList').click();

        await expect(page.locator('body')).toContainText('A2: O’zapft is!');
        await expect(page.locator('body')).toContainText('Ä1');

        const href = await page.locator('.amendment1').first().getAttribute('href');
        expect(href).not.toBeNull();
        const url = new URL(href as string, page.url()).toString();
        const response = await page.request.get(url);
        expect(response.ok()).toBeTruthy();
        const body = await response.body();
        expect(body.length).toBeGreaterThan(0);
    });
});
