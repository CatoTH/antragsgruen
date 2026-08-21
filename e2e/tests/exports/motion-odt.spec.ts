import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Exports: motion ODT', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('single motion ODT from admin interface', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);

        await page.locator('#motionListLink').click();
        await expect(page.locator('h1')).toContainText(/liste: anträge/i);

        const href = await page
            .locator('.adminMotionTable .motion3 a.odt')
            .first()
            .getAttribute('href');
        expect(href).not.toBeNull();
        const url = new URL(href as string, page.url()).toString();
        const response = await page.request.get(url);
        expect(response.ok()).toBeTruthy();
        const body = await response.body();
        expect(body.length).toBeGreaterThan(0);
        const head = body.subarray(0, 4).toString('latin1');
        expect(head).toBe('PK\x03\x04');
    });
});
