import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';

test.describe('Merging: simplified mode for users', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('users see simplified merging mode when enabled', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);
        await page.goto('/stdparteitag/std-parteitag/admin/motiontypes/type/1');

        await expect(page.locator('#initiatorsCanMerge0')).toBeChecked();
        await page.locator('#initiatorsCanMerge1').check();
        await page.locator('.adminTypeForm [name="save"]').click();
        await expect(page.locator('#initiatorsCanMerge1')).toBeChecked();

        await page.goto('/stdparteitag/std-parteitag');
        await page.goto('/stdparteitag/std-parteitag/motion/58');
        await logout(page);
        await loginAsStdUser(page);

        await page.locator('.sidebarActions .mergeamendments').click();
        await expect(page.locator('.motionMergeInit')).toHaveCount(0);
        await expect(page.locator('.motionMergeForm')).toBeVisible();
        await expect(page.locator('.motionData .alert-info')).toHaveCount(0);
        await expect(page.locator('.newAmendments')).toHaveCount(0);
        await expect(page.locator('.mergeActionHolder')).toHaveCount(0);
    });
});
