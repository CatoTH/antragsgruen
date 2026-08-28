import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';

test.describe('Merging: single amendment as user without collision', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('user merges amendment without collision', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdUser(page);
        await page.goto('/stdparteitag/std-parteitag/motion/321-o-zapft-is/276');
        await expect(page.locator('#sidebar')).not.toContainText('In den Antrag übernehmen');

        await logout(page);
        await loginAsStdAdmin(page);
        await page.goto('/stdparteitag/std-parteitag/admin/motiontypes/type/1');
        await expect(page.locator('#initiatorsCanMerge0')).toBeChecked();
        await page.locator('#initiatorsCanMerge1').check();
        await page.locator('.adminTypeForm [name="save"]').first().click();
        await expect(page.locator('#initiatorsCanMerge1')).toBeChecked();

        await logout(page);
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdUser(page);

        await page.goto('/stdparteitag/std-parteitag/motion/321-o-zapft-is');
        await expect(page.locator('.bookmarks')).toContainText('Ä2');
        await expect(page.locator('.bookmarks')).toContainText('Ä7');
        await expect(page.locator('p')).toContainText('Biawambn gscheid: Griasd');

        await page.goto('/stdparteitag/std-parteitag/motion/321-o-zapft-is/3');
        await expect(page.locator('#sidebar')).toContainText('In den Antrag übernehmen');
        await page.locator('#sidebar .mergeIntoMotion a').click();
        await expect(page.locator('h1')).toContainText('Kann nicht automatisch übernommen werden');
        await expect(page.locator('ul')).toContainText('Ä6 zu A2');
        await expect(page.locator('#amendmentMergeForm')).toHaveCount(0);

        await page.goto('/stdparteitag/std-parteitag/motion/321-o-zapft-is/276');
        await expect(page.locator('#sidebar')).toContainText('In den Antrag übernehmen');
        await page.locator('#sidebar .mergeIntoMotion a').click();
        await expect(page.locator('#amendmentMergeForm')).toBeVisible();
        await page.locator('#motionTitlePrefix').fill('A2new');
        await page.locator('#amendmentMergeForm [name="save"]').click();
        await expect(page.locator('body')).toContainText('Der Änderungsantrag wurde eingepflegt.');
        await page.locator('.btn-primary').click();

        await expect(page.locator('h1')).toContainText('A2new');
        await expect(page.locator('.bookmarks')).toContainText('Ä2');
        await expect(page.locator('.bookmarks')).not.toContainText('Ä7');
        await expect(page.locator('p')).not.toContainText('Biawambn gscheid: Griasd');
        await expect(page.locator('p')).toContainText('Biawambn gscheid:');
        await expect(page.locator('p')).toContainText('Griasd eich midnand');

        await page.evaluate(() => {
            const btn = document.querySelector('.motionDataTable .btnHistoryOpener') as HTMLElement | null;
            if (btn) btn.click();
        });
        await expect(page.locator('.motionHistory')).toContainText('Version 2');
        await page.locator('.motionHistory a.motion2').click();
        await expect(page.locator('h1')).toContainText('A2:');
        await expect(page.locator('.bookmarks')).not.toContainText('Ä2');
        await expect(page.locator('.bookmarks')).toContainText('Ä7');
    });
});
