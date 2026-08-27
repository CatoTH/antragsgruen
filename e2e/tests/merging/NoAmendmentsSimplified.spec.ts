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

        await test.step('enable merging for users in restricted mode', async () => {
            await expect(page.locator('#initiatorsCanMerge0')).toBeChecked();
            await page.locator('#initiatorsCanMerge1').first().check();
            await page.locator('.adminTypeForm [name="save"]').first().click();
            await expect(page.locator('#initiatorsCanMerge1')).toBeChecked();

            await page.goto('/stdparteitag/std-parteitag');
            await page.goto('/stdparteitag/std-parteitag/motion/58');
            await logout(page);
            await loginAsStdUser(page);
        });

        await test.step('ensure I see the simplified version of the form', async () => {
            await page.locator('.sidebarActions .mergeamendments').click();
            await expect(page.locator('.motionMergeInit').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.motionMergeForm').first()).toBeVisible();
            await expect(page.locator('.motionData .alert-info').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.newAmendments').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.mergeActionHolder').filter({ visible: true })).toHaveCount(0);
        });
    });
});
