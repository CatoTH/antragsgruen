import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { FIRST_FREE_MOTION_ID } from '../../utils/constants';

test.describe('Merging: single amendment as admin', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('merge single amendment into motion', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);
        await page.goto('/stdparteitag/std-parteitag/motion/321-o-zapft-is/274');
        await page.locator('#sidebar .mergeIntoMotion a').click();
        await page.waitForTimeout(1000);
        await page.locator('#amendmentStatus').selectOption('6');
        await page.locator('#otherAmendmentsStatus1').selectOption('5');
        await page.evaluate(() => {
            const btn = document.querySelector('.save-row .goto_2') as HTMLElement | null;
            if (btn) btn.click();
        });
        await page.waitForTimeout(1000);
        await page.locator('.checkAmendmentCollisions').click();
        await page.waitForTimeout(1500);

        await expect(page.locator('.amendmentCollisionsHolder .alert-danger')).toBeVisible();
        await expect(page.locator('del')).toContainText('Wui helfgod Wiesn');
        await expect(page.locator('ins')).toContainText('Alternatives Ende');
        await page.locator('#amendmentMergeForm [name="save"]').click();
        await expect(page.locator('.alert-success')).toContainText(
            'Der Änderungsantrag wurde eingepflegt.',
        );

        await page.locator('.alert-success .btn-primary').click();
        await expect(page.locator('h1')).toContainText('A2');
        await expect(page.locator('.motionDataTable .historyOpener .currVersion')).toContainText('Version 2');
        await expect(page.locator('body')).toContainText('Alternatives Ende');
        await expect(page.locator('body')).not.toContainText('Xaver Prosd eana an a bravs');
        await expect(page.locator('body')).toContainText('Ä2');
        await expect(page.locator('body')).toContainText('Ä3');
        await expect(page.locator('body')).not.toContainText('Ä1');
        await expect(page.locator('body')).not.toContainText('Ä6');

        await page.goto('/stdparteitag/std-parteitag/motion/2');
        await expect(page.locator('.alert-danger.motionReplacedBy')).toBeVisible();

        await page.goto(`/stdparteitag/std-parteitag/motion/321-o-zapft-is/${FIRST_FREE_MOTION_ID}`);
        await page.goto(`/stdparteitag/std-parteitag/amendment/${FIRST_FREE_MOTION_ID}-321-o-zapft-is/272`);
        await page.locator('#sidebar .mergeIntoMotion a').click();
        await page.waitForTimeout(1000);
        await page.evaluate(() => {
            const btn = document.querySelector('.save-row .goto_2') as HTMLElement | null;
            if (btn) btn.click();
        });
        await page.waitForTimeout(1000);
        await expect(page.locator('.versionSelector')).toHaveCount(0);
        await page.evaluate(() => {
            const inp = document.querySelector('.modifySelector input') as HTMLElement | null;
            if (inp) inp.click();
        });
        await page.waitForTimeout(1000);
        await page.evaluate(() => {
            const w = window as any;
            const ed = w.CKEDITOR.instances.new_paragraphs_original_2_7;
            ed.setData(ed.getData() + '<p>A modified adaption</p>');
        });

        await page.locator('.checkAmendmentCollisions').click();
        await page.waitForTimeout(1000);
        await expect(page.locator('.amendmentCollisionsHolder .alert-success')).toBeVisible();
        await page.locator('#amendmentMergeForm [name="save"]').click();
        await expect(page.locator('.alert-success')).toContainText(
            'Der Änderungsantrag wurde eingepflegt.',
        );

        await page.locator('.alert-success .btn-primary').click();
        await expect(page.locator('h1')).toContainText('A2');
        await page.evaluate(() => {
            const btn = document.querySelector('.motionDataTable .btnHistoryOpener') as HTMLElement | null;
            if (btn) btn.click();
        });
        await expect(page.locator('.motionDataTable .motionHistory a')).toContainText('Version 2');
        await expect(page.locator('.motionDataTable .motionHistory .currVersion')).toContainText('Version 3');
        await expect(page.locator('p')).toContainText('A modified adaption');
        await expect(page.locator('body')).toContainText('Something dahoam');
        await expect(page.locator('body')).not.toContainText('Ä4');
    });
});
