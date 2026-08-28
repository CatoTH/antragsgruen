import { test, expect } from '../../fixtures';

async function downloadFeedAndCheck(
    page: import('@playwright/test').Page,
    selector: string,
    needles: string[],
): Promise<void> {
    const href = await page.locator(selector).first().getAttribute('href');
    expect(href).not.toBeNull();
    const url = new URL(href as string, page.url()).toString();
    const response = await page.request.get(url);
    expect(response.ok()).toBeTruthy();
    const text = await response.text();
    for (const needle of needles) {
        expect(text).toContain(needle);
    }
}

test.describe('Exports: feeds', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('motion feed', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('#sidebar .feeds a').click();

        await expect(page.locator('.feedMotions')).toBeVisible();
        await downloadFeedAndCheck(page, '.feedMotions', ['O’zapft is!', 'Test']);
    });

    test('amendment feed', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('#sidebar .feeds a').click();

        await expect(page.locator('.feedAmendments')).toBeVisible();
        await downloadFeedAndCheck(page, '.feedAmendments', ['Tester', 'Ä1']);
    });

    test('overall feed', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('#sidebar .feeds a').click();

        await expect(page.locator('.feedAll')).toBeVisible();
        await downloadFeedAndCheck(page, '.feedAll', [
            'O’zapft is!',
            'Test',
            'Tester',
            'Ä1',
            'Oamoi a Maß',
            'Auf gehds beim Schichtl pfiad',
        ]);
    });
});
