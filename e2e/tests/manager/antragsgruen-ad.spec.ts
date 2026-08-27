import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';

test.describe('Manager: Antragsgrün ad visibility', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('see the ad by default', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await test.step('deactivate the ad', async () => {
            await expect(page.locator('#sidebar')).toContainText(/dein antragsgrün/i);
        });
    });

    test('admin can disable and re-enable the ad', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/admin/appearance');
        await page.locator('#showAntragsgruenAd').first().uncheck();
        await page.locator('#consultationAppearanceForm [name="save"]').click();

        await page.goto('/stdparteitag/std-parteitag');
        await test.step('activate the ad again', async () => {
            await expect(page.locator('#sidebar').getByText(/dein antragsgrün/i).filter({ visible: true })).toHaveCount(0);

            await page.goto('/stdparteitag/std-parteitag/admin/appearance');
            await page.locator('#showAntragsgruenAd').first().check();
            await page.locator('#consultationAppearanceForm [name="save"]').click();

            await page.goto('/stdparteitag/std-parteitag');
            await expect(page.locator('#sidebar')).toContainText(/dein antragsgrün/i);

            await logout(page);
        });
    });
});
