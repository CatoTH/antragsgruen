import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Merging: deleted referenced by modification', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('merge view handles deleted amendment with proposed procedure', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);
        await page.goto('/stdparteitag/std-parteitag/admin/motion-list');
        await page.locator('a[href*="amendment-edit/281"]').first().click();

        await page.locator('#amendmentStatus').selectOption('-3');
        await page.locator('#amendmentUpdateForm [name="save"]').click();

        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630');
        await page.locator('#sidebar .mergeamendments a').click();
        await page.waitForTimeout(200);
        await expect(page.locator('.amendment281').first()).toHaveCount(0);
        await page.evaluate(() => {
            const btn = document.querySelector('.toMergeAmendments .selectAll') as HTMLElement | null;
            if (btn) btn.click();
        });
        await page.waitForTimeout(200);
        await page.locator('.mergeAllRow [type="submit"]').click();
        await page.waitForTimeout(500);

        await expect(page.locator('body')).not.toContainText('Ä3');

        await expect(page.locator('body')).toContainText('Ä4');
        await expect(page.locator('ins')).toContainText('Zombie');
    });
});
