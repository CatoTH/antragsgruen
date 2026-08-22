import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Appearance: motion data mode', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('motion data visible by default, can be set to none or mini', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('.motionLink1').first().click();
        await page.locator('.motionData').waitFor();

        await expect(page.locator('.motionDataTable')).toBeVisible();
        await expect(page.locator('.motionDataTable')).toContainText('Testuser');
        await expect(page.locator('.motionDataTable')).toContainText('Test2');

        await loginAsStdAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/admin/appearance');
        await page.locator('#motiondataMode').selectOption('0');
        await page.locator('#consultationAppearanceForm [name="save"]').click();

        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('.motionLink1').first().click();
        await page.locator('.motionData').waitFor();
        await expect(page.locator('.motionDataTable')).toHaveCount(0);

        await page.goto('/stdparteitag/std-parteitag/admin/appearance');
        await page.locator('#motiondataMode').selectOption('1');
        await page.locator('#consultationAppearanceForm [name="save"]').click();

        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('.motionLink1').first().click();
        await page.locator('.motionData').waitFor();
        await expect(page.locator('.motionDataTable')).toBeVisible();
        await expect(page.locator('.motionDataTable')).toContainText('Testuser');
        await expect(page.locator('.motionDataTable')).not.toContainText('Test2');
    });
});
