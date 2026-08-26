import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Appearance: show feeds', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('feeds hidden when motion type policies are nobody', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('#sidebar .feeds a').click();
        await expect(page.locator('.feedMotions').first()).toBeVisible();
        await expect(page.locator('.feedAmendments').first()).toBeVisible();
        await expect(page.locator('.feedComments').first()).toBeVisible();
        await expect(page.locator('.feedAll').first()).toBeVisible();

        await loginAsStdAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/admin/motion-type/type?motionTypeId=1');
        await test.step('deactivate some feeds', async () => {
            await page.locator('#typePolicyMotions').first().selectOption('1');
            await page.locator('#typePolicyAmendments').first().selectOption('1');
            await page.locator('#typePolicyComments').first().selectOption('1');
            await page.locator('.adminTypeForm [name="save"]').first().click();

            await page.goto('/stdparteitag/std-parteitag');
            await page.locator('#sidebar .feeds a').click();
            await expect(page.locator('.feedMotions').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.feedAmendments').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.feedComments').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.feedAll').filter({ visible: true })).toHaveCount(0);

            await page.goto('/stdparteitag/std-parteitag/admin/motion-type/type?motionTypeId=1');
        });

        await test.step('activate the feeds again', async () => {
            await page.locator('#typePolicyMotions').first().selectOption('0');
            await page.locator('#typePolicyAmendments').first().selectOption('0');
            await page.locator('#typePolicyComments').first().selectOption('0');
            await page.locator('.adminTypeForm [name="save"]').first().click();

            await page.goto('/stdparteitag/std-parteitag');
            await page.locator('#sidebar .feeds a').click();
            await expect(page.locator('.feedMotions').first()).toBeVisible();
            await expect(page.locator('.feedAmendments').first()).toBeVisible();
            await expect(page.locator('.feedComments').first()).toBeVisible();
            await expect(page.locator('.feedAll').first()).toBeVisible();
        });
    });
});
