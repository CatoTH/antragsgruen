import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { FIRST_FREE_MOTION_ID } from '../../utils/constants';
import { gotoAmendment } from '../../utils/navigation';

test.describe('Merging: single amendment as user with collision', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('user merges colliding amendment with godlike mode', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdUser(page);
        await gotoAmendment(page, true, '321-o-zapft-is', 274);
        await test.step('ensure I cannot merge amendments now', async () => {
            await expect(page.locator('#sidebar').getByText('In den Antrag übernehmen').filter({ visible: true })).toHaveCount(0);

            await logout(page);
            await loginAsStdAdmin(page);
            await page.goto('/stdparteitag/std-parteitag/admin/motiontypes/type/1');
        });

        await test.step('enable merging for users in godlike mode', async () => {
            await expect(page.locator('#initiatorsCanMerge0')).toBeChecked();
            await page.locator('#initiatorsCanMerge2').first().check();
            await page.locator('.adminTypeForm [name="save"]').first().click();
            await expect(page.locator('#initiatorsCanMerge2')).toBeChecked();

            await logout(page);
            await page.goto('/stdparteitag/std-parteitag');
            await loginAsStdUser(page);

            await page.goto('/stdparteitag/std-parteitag/motion/321-o-zapft-is');
        });

        await expect(page.locator('.bookmarks')).toContainText('Ä2');
        await expect(page.locator('.bookmarks')).toContainText('Ä7');
        await expect(page.locator('body')).toContainText('Wui helfgod Wiesn');
        await expect(page.locator('body')).not.toContainText('Alternatives Ende', { useInnerText: true });

        await gotoAmendment(page, true, '321-o-zapft-is', 274);
        await expect(page.locator('del')).toContainText('Wui helfgod Wiesn');
        await expect(page.locator('ins')).toContainText('Alternatives Ende');
        await expect(page.locator('#sidebar')).toContainText('In den Antrag übernehmen');
        await page.locator('#sidebar .mergeIntoMotion a').click();
        await expect(page.locator('h1').getByText('Kann nicht automatisch übernommen werden').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.otherAmendmentStatus').filter({ visible: true })).toHaveCount(0);
        await page.locator('#amendmentStatus').first().selectOption('6');
        await page.evaluate(() => {
            const btn = document.querySelector('.save-row .goto_2') as HTMLElement | null;
            if (btn) btn.click();
        });
        await page.waitForTimeout(1000);
        await page.locator('.checkAmendmentCollisions').click();
        await page.waitForTimeout(2000);
        await expect(page.locator('del')).toContainText('Wui helfgod Wiesn');
        await expect(page.locator('ins')).toContainText('Woibbadinga damischa owe gwihss Sauwedda');
        await page.evaluate(() => {
            const w = window as any;
            const ed = w.CKEDITOR.instances.amendmentOverride_3_2_7;
            ed.setData(ed.getData() + '<p>Alternative ending</p>');
        });
        await page.locator('#amendmentMergeForm [name="save"]').click();
        await expect(page.locator('.alert-success')).toContainText(
            'Der Änderungsantrag wurde eingepflegt.',
        );

        await test.step('check the changes were made', async () => {
            await page.locator('.alert-success .btn-primary').click();
            await expect(page.locator('h1')).toContainText('A2');
            await expect(page.locator('.motionHistory')).toContainText('Version 2');
            await expect(page.locator('body')).not.toContainText('Wui helfgod Wiesn', { useInnerText: true });
        });

        await test.step('see the overridden amendment changes', async () => {
            await expect(page.locator('body')).toContainText('Alternatives Ende');

            await page.goto(
                `/stdparteitag/std-parteitag/amendment/${FIRST_FREE_MOTION_ID}-321-o-zapft-is/3`,
            );
            await expect(page.locator('del')).toContainText('Alternatives Ende');
            await expect(page.locator('ins')).toContainText('Xaver Prosd eana an a bravs');
            await expect(page.locator('p.inserted')).toContainText('Alternative ending');
        });

    });
});
