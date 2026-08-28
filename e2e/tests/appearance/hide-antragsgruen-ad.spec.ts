import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Appearance: hide Antragsgrün ad', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('ad is visible by default, can be hidden and shown', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('#sidebar')).toContainText('Dein Antragsgrün');

        await loginAsStdAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/admin/appearance');
        await page.locator('#showAntragsgruenAd').uncheck();
        await page.locator('#consultationAppearanceForm [name="save"]').click();

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('#sidebar')).not.toContainText('Dein Antragsgrün');

        await page.goto('/stdparteitag/std-parteitag/admin/appearance');
        await page.locator('#showAntragsgruenAd').check();
        await page.locator('#consultationAppearanceForm [name="save"]').click();

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('#sidebar')).toContainText('Dein Antragsgrün');
    });
});
