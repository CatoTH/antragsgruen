import { test, expect } from '../../fixtures';

test.describe('DeletedItems', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('check deleted items not visible on consultation home', async ({ page }) => {
        await page.goto('/stdparteitag/1laenderrat2015');

        await expect(page.locator('.amendment136').first()).not.toBeVisible();
        await expect(page.locator('.motionLink50').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.motionLink47').first()).toBeVisible();
        await expect(page.locator('.motionLink52').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.amendment58').first()).not.toBeVisible();
    });
});