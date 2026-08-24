import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Appearance: show feeds', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('feeds hidden when motion type policies are nobody', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('#sidebar .feeds a').click();
        await expect(page.locator('.feedMotions')).toBeVisible();
        await expect(page.locator('.feedAmendments')).toBeVisible();
        await expect(page.locator('.feedComments')).toBeVisible();
        await expect(page.locator('.feedAll')).toBeVisible();

        await loginAsStdAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/admin/motion-type/type?motionTypeId=1');
        await page.locator('#typePolicyMotions').selectOption('1');
        await page.locator('#typePolicyAmendments').selectOption('1');
        await page.locator('#typePolicyComments').selectOption('1');
        await page.locator('.adminTypeForm [name="save"].first()').click();

        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('#sidebar .feeds a').click();
        await expect(page.locator('.feedMotions')).toHaveCount(0);
        await expect(page.locator('.feedAmendments')).toHaveCount(0);
        await expect(page.locator('.feedComments')).toHaveCount(0);
        await expect(page.locator('.feedAll')).toHaveCount(0);

        await page.goto('/stdparteitag/std-parteitag/admin/motion-type/type?motionTypeId=1');
        await page.locator('#typePolicyMotions').selectOption('0');
        await page.locator('#typePolicyAmendments').selectOption('0');
        await page.locator('#typePolicyComments').selectOption('0');
        await page.locator('.adminTypeForm [name="save"].first()').click();

        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('#sidebar .feeds a').click();
        await expect(page.locator('.feedMotions')).toBeVisible();
        await expect(page.locator('.feedAmendments')).toBeVisible();
        await expect(page.locator('.feedComments')).toBeVisible();
        await expect(page.locator('.feedAll')).toBeVisible();
    });
});
