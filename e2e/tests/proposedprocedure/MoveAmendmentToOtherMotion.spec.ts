import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { gotoAmendment } from '../../utils/navigation';

test.describe('Proposed procedure: move amendment to other motion', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('move amendment to other motion via proposed procedure', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await test.step('Set the status of the preplacing amendment (needs to be done first)', async () => {
            await expect(page.locator('.amendmentRow1').first()).toBeVisible();

            await loginAsStdAdmin(page);
            await page.goto('/stdparteitag/std-parteitag/admin/motion-list');
            await page.locator('a[href*="amendment-edit/1"]').first().click();
            await page.locator('#amendmentStatus').first().selectOption('28');
            await page.locator('#amendmentUpdateForm [name="save"]').click();

            await page.goto('/stdparteitag/std-parteitag');
            await expect(page.locator('.amendmentRow1').filter({ visible: true })).toHaveCount(0);

            await gotoAmendment(page, true, 'Testing_proposed_changes-630', 279);
        });

        await test.step('Set the status of the actual, to be moved, amendment', async () => {
            await page.locator('.proposedChangesOpener button').click();
            await page.waitForTimeout(300);
            await expect(page.locator('#proposedChanges').first()).toBeVisible();

            await expect(page.locator('.status_28').filter({ visible: true })).toHaveCount(0);
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
            await expect(page.locator('.status_28').first()).toBeVisible();

            await page.locator('#movedToOtherMotion').first().selectOption('1');
            await expect(page.locator('#proposedChanges .saving').first()).toBeVisible();
            await page.evaluate(() => {
                const btn = document.querySelector('#proposedChanges .saving button') as HTMLElement | null;
                if (btn) btn.click();
            });
        });

        await test.step('see the effects in the proposed procedure', async () => {
            await page.locator('#motionListLink').click();
            await page.locator('#exportProcedureBtn').click();
            await page.locator('.exportProcedureDd .linkProcedureIntern a').click();
            await expect(page.locator('.proposedProcedureOverview').first()).toBeVisible();
            await expect(page.locator('.amendment1').first()).toContainText('Vorgeschlagene Verschiebung von anderem Antrag');
            await expect(page.locator('.amendment279').first()).toContainText('Verschoben zu anderem Antrag');
            await expect(page.locator('.amendment1 .inserted')).toContainText('Oamoi a Maß und no a Maß');

            await page.goto('/stdparteitag/std-parteitag');
            await page.locator('.amendmentRow279 a').first().click();
        });

        await test.step('see the effects in the amendment view', async () => {
            await expect(page.locator('h2').filter({ hasText: 'Verfahrensvorschlag: Antragstext' }).first()).toBeVisible();
            await expect(page.locator('#pp_section_2_0 .inserted')).toContainText('Oamoi a Maß');
            await expect(page.locator('#pp_section_2_0 a')).toContainText('A2: O\u2019zapft is!');

            await expect(page.locator('ins')).toContainText('A small replacement');

            await page.goto('/stdparteitag/std-parteitag');
            await page.locator('.motionLink2').click();
            await expect(page.locator('body')).not.toContainText('Ä1', { useInnerText: true });
        });

        await test.step('be able to merge the moved amendment to its motion', async () => {
            await page.locator('#sidebar .mergeamendments a').click();
            await expect(page.locator('.amendment1').first()).toBeVisible();
            await expect(page.locator('#markAmendment1')).toBeChecked();
            await page.locator('#markAmendment3').first().check();
            await page.locator('.mergeAllRow [type="submit"]').click();

            await page.waitForTimeout(500);
            await expect(page.locator('.toggleAmendment1.toggleActive').first()).toBeVisible();
            await expect(page.locator('.toggleAmendment3.toggleActive').first()).toBeVisible();

            await expect(page.locator('.ice-ins')).toContainText('Oamoi a Maß');
            await expect(page.locator('.ice-del')).toContainText('Woibbadinga');
        });
    });
});
