import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Exports: amendment PDF', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    async function downloadAndCheckPdf(
        page: import('@playwright/test').Page,
        selector: string,
    ): Promise<void> {
        const href = await page.locator(selector).first().getAttribute('href');
        expect(href).not.toBeNull();
        const url = new URL(href as string, page.url()).toString();
        const response = await page.request.get(url);
        expect(response.ok()).toBeTruthy();
        const body = await response.body();
        expect(body.length).toBeGreaterThan(0);
        const head = body.subarray(0, 4).toString('latin1');
        expect(head).toBe('%PDF');
    }

    test('single amendment PDF as anonymous user', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('.amendment1').first().click();
        await page.locator('.motionData').waitFor();
        await downloadAndCheckPdf(page, '#sidebar .download a');
    });

    test('single amendment PDF from admin interface', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);

        await page.locator('#motionListLink').click();
        await expect(page.locator('h1')).toContainText(/liste: anträge/i);
        await downloadAndCheckPdf(page, '.adminMotionTable .amendment1 a.pdf');
    });

    test('amendment PDF compilation from home page', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await downloadAndCheckPdf(page, '#sidebar .amendmentPdfs');
    });

    test('amendment PDF compilation from admin interface', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);

        await page.locator('#motionListLink').click();
        await expect(page.locator('h1')).toContainText(/liste: anträge/i);
        await page.locator('#exportAmendmentsBtn').click();
        await downloadAndCheckPdf(page, '.amendmentPDF');
    });
});
