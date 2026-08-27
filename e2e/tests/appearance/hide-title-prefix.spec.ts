import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Appearance: hide title prefix', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('title prefix shown by default, hidden after setting', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('body')).toContainText('A2');
        await expect(page.locator('body')).toContainText('Ä1');

        await loginAsStdAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/admin/appearance');
        await test.step('disable title prefixes', async () => {
            await page.locator('#hideTitlePrefix').first().check();
            await page.locator('#consultationAppearanceForm [name="save"]').click();

            await page.goto('/stdparteitag/std-parteitag');
            await expect(page.locator('body')).not.toContainText('A2', { useInnerText: true });

            await page.locator('.motionLink2').click();
            await page.locator('.motionData').waitFor();
            await expect(page.locator('body')).not.toContainText('A2', { useInnerText: true });

            await page.locator('.amendmentCreate a').click();
            await expect(page.locator('body')).not.toContainText('A2', { useInnerText: true });

            await page.goto('/stdparteitag/std-parteitag');
            await page.locator('.amendment1').first().click();
            await page.locator('.motionData').waitFor();
            await expect(page.locator('body')).not.toContainText('A2', { useInnerText: true });
        });
    });
});
