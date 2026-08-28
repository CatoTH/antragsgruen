import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Appearance: translation services', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('translate widget hidden by default, can be activated', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('.translateWidget')).toHaveCount(0);

        await page.locator('.motionLink1').first().click();
        await page.locator('.motionData').waitFor();
        await expect(page.locator('.translateWidget')).toHaveCount(0);

        await page.locator('.amendment1').first().click();
        await page.locator('.motionData').waitFor();
        await expect(page.locator('.translateWidget')).toHaveCount(0);

        await loginAsStdAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/admin');
        await page.locator('#appearanceLink').click();
        await expect(page.locator('#translationService')).not.toBeChecked();
        await expect(page.locator('.translationService .services')).toHaveCount(0);

        await page.evaluate(() => {
            const el = document.querySelector('#translationService') as HTMLInputElement;
            if (el) {
                el.checked = true;
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await page
            .locator("input[name='translationSpecificService'][value='bing']")
            .check();
        await page.locator('#consultationAppearanceForm [name="save"]').click();

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('.translateWidget')).toBeVisible();

        await page.locator('.motionLink1').first().click();
        await page.locator('.motionData').waitFor();
        await expect(page.locator('.translateWidget')).toBeVisible();

        await page.locator('.amendment1').first().click();
        await page.locator('.motionData').waitFor();
        await expect(page.locator('.translateWidget')).toBeVisible();

        await expect(page.locator('.dropdown-menu')).toHaveCount(0);
        await page.evaluate(() => {
            const el = document.querySelector('#translatePageBtn');
            if (el) {
                el.dispatchEvent(
                    new MouseEvent('click', { bubbles: true, cancelable: true, view: window }),
                );
            }
        });
        await expect(page.locator('.dropdown-menu')).toBeVisible();
        await expect(page.locator('.dropdown-menu')).toContainText('Español');
    });
});
