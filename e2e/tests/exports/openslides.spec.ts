import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

async function downloadAndCheckHasContent(
    page: import('@playwright/test').Page,
    selector: string,
): Promise<{ text: string; bytes: Buffer }> {
    const href = await page.locator(selector).first().getAttribute('href');
    expect(href).not.toBeNull();
    const url = new URL(href as string, page.url()).toString();
    const response = await page.request.get(url);
    expect(response.ok()).toBeTruthy();
    const text = await response.text();
    expect(text.length).toBeGreaterThan(0);
    return { text, bytes: Buffer.from(text) };
}

test.describe('Exports: OpenSlides', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enable OpenSlides export and download all artefacts', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);

        await page.locator('#motionListLink').click();
        await expect(page.locator('h1')).toContainText(/liste: anträge/i);

        await expect(page.locator('#exportOpenslidesBtn')).toHaveCount(0);
        await expect(page.locator('.activateOpenslides')).toHaveCount(0);

        await page.locator('#activateFncBtn').click();
        await expect(page.locator('.activateOpenslides')).toBeVisible();
        await page.locator('.activateOpenslides').click();
        await expect(page.locator('#exportOpenslidesBtn')).toBeVisible();

        await page.locator('#exportOpenslidesBtn').click();
        await expect(page.locator('.exportOpenslidesDd .users')).toBeVisible();

        const usersFile = await downloadAndCheckHasContent(page, '.exportOpenslidesDd .users');
        expect(usersFile.text).toContain('Lischke');

        const motionsFile = await downloadAndCheckHasContent(
            page,
            '.exportOpenslidesDd .slidesMotionType1',
        );
        expect(motionsFile.text).toContain('line-through');

        const amendmentsFile = await downloadAndCheckHasContent(
            page,
            '.exportOpenslidesDd .amendments',
        );
        expect(amendmentsFile.text).toContain('Von Zeile 9 bis 10');
    });
});
