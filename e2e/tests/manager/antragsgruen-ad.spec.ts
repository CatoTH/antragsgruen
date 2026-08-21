import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';

test.describe('Manager: Antragsgrün ad visibility', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('see the ad by default', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('#sidebar')).toContainText(/dein antragsgrün/i);
    });

    test('admin can disable and re-enable the ad', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/admin/index/appearance');
        await page.locator('#showAntragsgruenAd').uncheck();
        await page.locator('#consultationAppearanceForm [name="save"]').click();

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('#sidebar')).not.toContainText(/dein antragsgrün/i);

        await page.goto('/stdparteitag/std-parteitag/admin/index/appearance');
        await page.locator('#showAntragsgruenAd').check();
        await page.locator('#consultationAppearanceForm [name="save"]').click();

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('#sidebar')).toContainText(/dein antragsgrün/i);

        await logout(page);
    });
});
