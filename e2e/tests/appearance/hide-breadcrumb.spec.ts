import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Appearance: hide breadcrumb', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('breadcrumb visible by default, can be hidden and shown', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('ol.breadcrumb').first()).toBeVisible();

        await loginAsStdAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/admin/appearance');
        await test.step('disable the breadcrumb', async () => {
            await page.locator('#showBreadcrumbs').first().uncheck();
            await page.locator('#consultationAppearanceForm [name="save"]').click();

            await page.goto('/stdparteitag/std-parteitag');
            await expect(page.locator('ol.breadcrumb').filter({ visible: true })).toHaveCount(0);

            await page.goto('/stdparteitag/std-parteitag/admin/appearance');
        });

        await test.step('enable it again', async () => {
            await page.locator('#showBreadcrumbs').first().check();
            await page.locator('#consultationAppearanceForm [name="save"]').click();

            await page.goto('/stdparteitag/std-parteitag');
            await expect(page.locator('ol.breadcrumb').first()).toBeVisible();
        });
    });
});
