import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Misc: discussion tags start layout', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('switch to discussion-tags layout, filter by tag, comment', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/admin/index/appearance');
        await page.locator('#startLayoutType').selectOption('2');
        await page.locator('#consultationAppearanceForm [name="save"]').click();

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('.tagList .tag1')).toContainText('Umwelt (5)');
        await expect(page.locator('.motionLink115')).toContainText('Listen-Test');
        await expect(page.locator('.motionLink58')).toContainText('Testantrag');
        await expect(page.locator('.expandableRecentComments')).toHaveCount(0);
        await expect(page.locator('.motionRow2 .comments')).toHaveCount(0);

        await page.evaluate(() => {
            const el = document.querySelector('.tagList .tag1');
            if (el) {
                el.dispatchEvent(
                    new MouseEvent('click', { bubbles: true, cancelable: true, view: window }),
                );
            }
        });
        await page.waitForTimeout(500);

        await expect(page.locator('.motionLink58')).not.toContainText('Testantrag');
        await expect(page.locator('.motionLink115')).toContainText('Listen-Test');

        await page.locator('.motionLink1').first().click();
        await page.locator('.motionData').waitFor();

        await page.locator('#comment_-1_-1_text').fill('Test-Kommentar');
        await page.locator('#comment_-1_-1_form [name="writeComment"]').click();

        await expect(page.locator('#comment1')).toContainText('Test-Kommentar');

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('.expandableRecentComments')).toBeVisible();
        await expect(page.locator('.motionComment')).toContainText('Test-Kommentar');
        await expect(page.locator('.motionRow2 .comments')).toBeVisible();
    });
});
