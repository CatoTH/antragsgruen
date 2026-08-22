import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';

test.describe('Merging: conflict resolving proposal', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('switch between proposed and original amendment versions', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.gotoMotionView(2);
        await loginAsStdAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630/280');
        await page.locator('.proposedChangesOpener button').click();
        await expect(page.locator('#proposedChanges')).toBeVisible();
        await page.evaluate(() => {
            const inp = document.querySelector(
                '#proposedChanges .proposalStatus6 input',
            ) as HTMLElement | null as HTMLInputElement | null;
            if (inp) {
                inp.checked = true;
                inp.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await page.locator('#proposedChanges .saving button').click();
        await page.waitForTimeout(1000);
        await page.locator('.resetText').click();
        await page.evaluate(() => {
            const w = window as any;
            const data = w.CKEDITOR.instances.sections_2_wysiwyg.getData();
            w.CKEDITOR.instances.sections_2_wysiwyg.setData(
                data.replace(/et ea rebum\.<\/p>/, 'et ea rebum noconflict.</p>'),
            );
        });
        await page.locator('#proposedChangeTextForm [name="save"]').click();
        await expect(page.locator('.alert-success')).toBeVisible();

        await home.gotoMotionView(118);
        await page.locator('#sidebar .mergeamendments a').click();
        await page.waitForTimeout(200);
        await page.locator('.toMergeAmendments .selectAll').click();
        await page.waitForTimeout(200);
        await page.locator('.mergeAllRow [type="submit"]').click();
        await page.waitForTimeout(500);

        await page.evaluate(() => {
            document.querySelectorAll('.none').forEach((el) => el.remove());
            document.querySelectorAll('#draftSavingPanel').forEach((el) => el.remove());
        });

        await expect(page.locator('#section_holder_2_1 ins')).toContainText('noconflict');
        await expect(page.locator('#paragraphWrapper_2_1 .collidingParagraph')).toHaveCount(0);
        await expect(page.locator('#paragraphWrapper_2_2 .appendedCollision')).toContainText('Zombie');

        await page.evaluate(() => {
            const btn = document.querySelector(
                '#paragraphWrapper_2_1 .amendmentStatus280 .dropdown-toggle',
            ) as HTMLElement | null;
            if (btn) btn.click();
        });
        await page.waitForTimeout(500);
        await page.evaluate(() => {
            const link = document.querySelector(
                '#paragraphWrapper_2_1 .amendmentStatus280 .versionorig a',
            ) as HTMLElement | null;
            if (link) link.click();
        });
        await page.waitForTimeout(1000);
        await expect(page.locator('#section_holder_2_1 ins')).not.toContainText('noconflict');
        await expect(page.locator('#paragraphWrapper_2_1 .collidingParagraph ins')).toContainText('A big replacement');

        await home.gotoMotionView(118);
        await page.locator('#sidebar .mergeamendments a').click();
        await page.waitForTimeout(200);
        await page.locator('.toMergeAmendments .selectAll').click();
        await page.evaluate(() => {
            const inp = document.querySelector(
                '.amendment280 input[value="orig"]',
            ) as HTMLElement | null as HTMLInputElement | null;
            if (inp) {
                inp.checked = true;
                inp.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await page.waitForTimeout(200);
        await page.locator('.mergeAllRow [type="submit"]').click();
        await page.waitForTimeout(500);

        await page.evaluate(() => {
            document.querySelectorAll('.none').forEach((el) => el.remove());
            document.querySelectorAll('#draftSavingPanel').forEach((el) => el.remove());
        });

        await expect(page.locator('#section_holder_2_1 ins')).not.toContainText('noconflict');
        await expect(page.locator('#paragraphWrapper_2_1 .collidingParagraph')).toBeVisible();
        await expect(page.locator('#paragraphWrapper_2_2 .appendedCollision')).toContainText('Zombie');

        await page.evaluate(() => {
            const btn = document.querySelector(
                '#paragraphWrapper_2_1 .amendmentStatus280 .dropdown-toggle',
            ) as HTMLElement | null;
            if (btn) btn.click();
        });
        await page.waitForTimeout(500);
        await page.evaluate(() => {
            const link = document.querySelector(
                '#paragraphWrapper_2_1 .amendmentStatus280 .versionprop a',
            ) as HTMLElement | null;
            if (link) link.click();
        });
        await page.waitForTimeout(1000);
        await expect(page.locator('#section_holder_2_1 ins')).toContainText('noconflict');
        await expect(page.locator('#paragraphWrapper_2_1 .collidingParagraph')).toHaveCount(0);
        await expect(page.locator('#paragraphWrapper_2_2 .appendedCollision')).toContainText('Zombie');
    });
});
