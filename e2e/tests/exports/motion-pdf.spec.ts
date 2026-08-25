import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';

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

test.describe('Exports: motion PDF', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('disable and re-enable motion-type PDF, download motion and PDF compilation', async ({
        page,
    }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/admin/motion-type/type?motionTypeId=1');
        await page.locator('.layout.php-1').click();
        await page.locator('.adminTypeForm [name="save"]').first().click();

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('body')).not.toContainText('PDF');
        await expect(page.locator('#sidebar .motionPdfCompilation')).toHaveCount(0);

        await page.locator('.motionLink2').click();
        await page.locator('.motionData').waitFor();
        await expect(page.locator('body')).not.toContainText('PDF');

        await page.goto('/stdparteitag/std-parteitag/admin/motion-type/type?motionTypeId=1');
        await page.locator('.layout.php0').click();
        await page.locator('.adminTypeForm [name="save"]').first().click();

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('body')).toContainText('PDF');
        await expect(page.locator('#sidebar .motionPdfCompilation')).toBeVisible();

        await logout(page);

        await page.locator('.motionLink3').click();
        await page.locator('.motionData').waitFor();
        await expect(page.locator('body')).toContainText('PDF');
        await expect(page.locator('body')).toContainText(
            'Seltsame Zeichen: & % $ # _ { } ~ ^ \\ \\today',
        );
        await downloadAndCheckPdf(page, '#sidebar .download a');
    });

    test('single motion PDF from admin interface', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);

        await page.locator('#motionListLink').click();
        await expect(page.locator('h1')).toContainText(/liste: anträge/i);
        await downloadAndCheckPdf(page, '.adminMotionTable .motion3 a.pdf');
    });

    test('PDF compilation of all motions', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await downloadAndCheckPdf(page, '.motionPdfCompilation');
    });
});
