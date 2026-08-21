import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Appearance: hide breadcrumb', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('breadcrumb visible by default, can be hidden and shown', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('ol.breadcrumb')).toBeVisible();

        await loginAsStdAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/admin/index/appearance');
        await page.locator('#showBreadcrumbs').uncheck();
        await page.locator('#consultationAppearanceForm [name="save"]').click();

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('ol.breadcrumb')).toHaveCount(0);

        await page.goto('/stdparteitag/std-parteitag/admin/index/appearance');
        await page.locator('#showBreadcrumbs').check();
        await page.locator('#consultationAppearanceForm [name="save"]').click();

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('ol.breadcrumb')).toBeVisible();
    });
});
