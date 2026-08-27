import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Misc: hidden amendments on consultation home', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('amendments hidden behind toggler when layout hides them', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/admin/appearance');
        await page.locator('#startLayoutType').first().selectOption('4');
        await page.locator('#consultationAppearanceForm [name="save"]').click();

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('.motionRow2 .amendmentsToggler.closed').first()).toBeVisible();
        await expect(page.locator('.amendmentRow1').filter({ visible: true })).toHaveCount(0);

        await page.evaluate(() => {
            const btn = document.querySelector(
                '.motionRow2 .amendmentsToggler button',
            );
            if (btn) {
                btn.dispatchEvent(
                    new MouseEvent('click', { bubbles: true, cancelable: true, view: window }),
                );
            }
        });

        await expect(page.locator('.amendmentRow1').first()).toBeVisible();
    });
});
