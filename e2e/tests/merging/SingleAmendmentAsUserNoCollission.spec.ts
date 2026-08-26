import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { gotoAmendment } from '../../utils/navigation';

test.describe('Merging: single amendment as user without collision', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('user merges amendment without collision', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdUser(page);
        await gotoAmendment(page, true, '321-o-zapft-is', 276);
        await test.step('ensure I cannot merge amendments now', async () => {
            await expect(page.locator('#sidebar').getByText('In den Antrag übernehmen').filter({ visible: true })).toHaveCount(0);

            await logout(page);
            await loginAsStdAdmin(page);
            await page.goto('/stdparteitag/std-parteitag/admin/motiontypes/type/1');
        });

        await test.step('enable merging for users in restricted mode', async () => {
            await expect(page.locator('#initiatorsCanMerge0')).toBeChecked();
            await page.locator('#initiatorsCanMerge1').first().check();
            await page.locator('.adminTypeForm [name="save"]').first().click();
            await expect(page.locator('#initiatorsCanMerge1')).toBeChecked();

            await logout(page);
            await page.goto('/stdparteitag/std-parteitag');
            await loginAsStdUser(page);

            await page.goto('/stdparteitag/std-parteitag/motion/321-o-zapft-is');
        });

        await test.step('merge amendments with collisions', async () => {
            await expect(page.locator('.bookmarks')).toContainText('Ä2');
            await expect(page.locator('.bookmarks')).toContainText('Ä7');
            await expect(page.locator('p').filter({ hasText: 'Biawambn gscheid: Griasd' }).first()).toBeVisible();

            await gotoAmendment(page, true, '321-o-zapft-is', 3);
        });

        await test.step('merge amendments without collisions', async () => {
            await expect(page.locator('#sidebar')).toContainText('In den Antrag übernehmen');
            await page.locator('#sidebar .mergeIntoMotion a').click();
            await expect(page.locator('h1')).toContainText('Kann nicht automatisch übernommen werden');
            await expect(page.locator('ul')).toContainText('Ä6 zu A2');
            await expect(page.locator('#amendmentMergeForm').filter({ visible: true })).toHaveCount(0);

            await gotoAmendment(page, true, '321-o-zapft-is', 276);
            await expect(page.locator('#sidebar')).toContainText('In den Antrag übernehmen');
            await page.locator('#sidebar .mergeIntoMotion a').click();
            await expect(page.locator('#amendmentMergeForm').first()).toBeVisible();
        });

        await test.step('see the new motion', async () => {
            await page.locator('#motionTitlePrefix').first().fill('A2new');
            await page.locator('#amendmentMergeForm [name="save"]').click();
            await expect(page.locator('body')).toContainText('Der Änderungsantrag wurde eingepflegt.');
            await page.locator('.btn-primary').click();

            await expect(page.locator('h1')).toContainText('A2new');
            await expect(page.locator('.bookmarks')).toContainText('Ä2');
            await expect(page.locator('.bookmarks').getByText('Ä7').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('p').getByText('Biawambn gscheid: Griasd').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('p').filter({ hasText: 'Biawambn gscheid:' }).first()).toBeVisible();
            await expect(page.locator('p').filter({ hasText: 'Griasd eich midnand' }).first()).toBeVisible();

            await page.evaluate(() => {
                const btn = document.querySelector('.motionDataTable .btnHistoryOpener') as HTMLElement | null;
                if (btn) btn.click();
            });
            await expect(page.locator('.motionHistory')).toContainText('Version 2');
            await page.locator('.motionHistory a.motion2').click();
            await expect(page.locator('h1')).toContainText('A2:');
            await expect(page.locator('.bookmarks').getByText('Ä2').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.bookmarks')).toContainText('Ä7');
        });

    });
});
