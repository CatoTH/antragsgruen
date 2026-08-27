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
        await test.step('disable the ad', async () => {
            await page.locator('#showAntragsgruenAd').first().uncheck();
            await page.locator('#consultationAppearanceForm [name="save"]').click();

            await page.goto('/stdparteitag/std-parteitag');
            await expect(page.locator('#sidebar').getByText('Dein Antragsgrün').filter({ visible: true })).toHaveCount(0);

            await page.goto('/stdparteitag/std-parteitag/admin/appearance');
        });

        await test.step('enable it again', async () => {
            await page.locator('#showAntragsgruenAd').first().check();
            await page.locator('#consultationAppearanceForm [name="save"]').click();

            await page.goto('/stdparteitag/std-parteitag');
            await expect(page.locator('#sidebar')).toContainText('Dein Antragsgrün');
        });
    });
});
