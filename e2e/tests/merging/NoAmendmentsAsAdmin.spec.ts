import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Merging: motion without amendments as admin', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('merge view for motion without amendments', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);
        await page.goto('/stdparteitag/std-parteitag/motion/58');
        await test.step('use merging for a motion without amendments', async () => {
            await page.locator('.sidebarActions .mergeamendments').click();
            await expect(page.locator('.motionMergeInit').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.motionMergeForm').first()).toBeVisible();
            await expect(page.locator('.motionData .alert-info').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.newAmendments').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.mergeActionHolder').filter({ visible: true })).toHaveCount(0);

            await page.waitForTimeout(1000);
            await page.evaluate(() => {
                const w = window as any;
                w.CKEDITOR.instances.sections_2_0_wysiwyg.setData(
                    '<p>An updated version of this motion</p>',
                );
            });

            await page.evaluate(() => {
                document.querySelectorAll('.none').forEach((el) => el.remove());
                document.querySelectorAll('#draftSavingPanel').forEach((el) => el.remove());
            });

            await expect(page.locator('#sections_2_0_wysiwyg')).toContainText(
                'An updated version of this motion',
            );
            await page.locator('.motionMergeForm [name="save"]').click();

            await expect(page.locator('body')).toContainText('An updated version of this motion');
            await page.locator('#motionConfirmForm [name="confirm"]').click();

            await expect(page.locator('body')).toContainText('Der Antrag wurde überarbeitet');
            await page.locator('#motionConfirmedForm [type="submit"]').click();

            await expect(page.locator('.motionTextHolder1')).toContainText(
                'An updated version of this motion',
            );
        });
    });
});
