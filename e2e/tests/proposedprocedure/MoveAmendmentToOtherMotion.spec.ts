import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Proposed procedure: move amendment to other motion', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('move amendment to other motion via proposed procedure', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('.amendmentRow1')).toBeVisible();

        await loginAsStdAdmin(page);
        await page.goto('/stdparteitag/std-parteitag/admin/motion-list');
        await page.locator('a[href*="amendment-edit/1"]').first().click();
        await page.locator('#amendmentStatus').selectOption('28');
        await page.locator('#amendmentUpdateForm [name="save"]').click();

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('.amendmentRow1')).toHaveCount(0);

        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630/279');

        await page.locator('.proposedChangesOpener button').click();
        await page.waitForTimeout(300);
        await expect(page.locator('#proposedChanges')).toBeVisible();

        await expect(page.locator('.status_28')).toHaveCount(0);
        await page.evaluate(() => {
            const inp = document.querySelector(
                '#proposedChanges .proposalStatus28 input',
            ) as HTMLElement | null as HTMLInputElement | null;
            if (inp) {
                inp.checked = true;
                inp.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await page.waitForTimeout(200);
        await expect(page.locator('.status_28')).toBeVisible();

        await page.locator('#movedToOtherMotion').selectOption('1');
        await expect(page.locator('#proposedChanges .saving')).toBeVisible();
        await page.evaluate(() => {
            const btn = document.querySelector('#proposedChanges .saving button') as HTMLElement | null;
            if (btn) btn.click();
        });

        await page.locator('#motionListLink').click();
        await page.locator('#exportProcedureBtn').click();
        await page.locator('.exportProcedureDd .linkProcedureIntern a').click();
        await expect(page.locator('.proposedProcedureOverview')).toBeVisible();
        await expect(page.locator('.amendment1').first()).toContainText('Vorgeschlagene Verschiebung von anderem Antrag');
        await expect(page.locator('.amendment279').first()).toContainText('Verschoben zu anderem Antrag');
        await expect(page.locator('.amendment1 .inserted')).toContainText('Oamoi a Maß und no a Maß');

        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('.amendmentRow279 a').first().click();

        await expect(page.locator('h2')).toContainText('Verfahrensvorschlag: Antragstext');
        await expect(page.locator('#pp_section_2_0 .inserted')).toContainText('Oamoi a Maß');
        await expect(page.locator('#pp_section_2_0 a')).toContainText('A2: O\u2019zapft is!');

        await expect(page.locator('ins')).toContainText('A small replacement');

        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('.motionLink2').click();
        await expect(page.locator('body')).not.toContainText('Ä1');
        await page.locator('#sidebar .mergeamendments a').click();
        await expect(page.locator('.amendment1').first()).toBeVisible();
        await expect(page.locator('#markAmendment1')).toBeChecked();
        await page.locator('#markAmendment3').check();
        await page.locator('.mergeAllRow [type="submit"]').click();

        await page.waitForTimeout(500);
        await expect(page.locator('.toggleAmendment1.toggleActive')).toBeVisible();
        await expect(page.locator('.toggleAmendment3.toggleActive')).toBeVisible();

        await expect(page.locator('.ice-ins')).toContainText('Oamoi a Maß');
        await expect(page.locator('.ice-del')).toContainText('Woibbadinga');
    });
});
