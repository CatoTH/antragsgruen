import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

async function downloadAndCheckHasContent(
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
}

test.describe('Exports: ODS', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('ODS exports for motions and amendments', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);

        await page.locator('#motionListLink').click();
        await expect(page.locator('h1')).toContainText(/liste: anträge/i);

        await test.step('test the list of motions in ODS-Format', async () => {
            await page.locator('#exportMotionBtn').click();
            await expect(page.locator('.motionODS1').first()).toBeVisible();
            await downloadAndCheckHasContent(page, '.motionODS1');

            await page.locator('#exportAmendmentsBtn').click();
            await expect(page.locator('.amendmentOds').first()).toBeVisible();
            await downloadAndCheckHasContent(page, '.amendmentOds');
        });
    });
});
