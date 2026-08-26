import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { gotoAmendment } from '../../utils/navigation';
import { dispatchClick } from '../../utils/dom';

test.describe('Proposed procedure: merge single amendment', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('switch between original and modified amendment versions when merging', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);
        await gotoAmendment(page, true, 'Testing_proposed_changes-630', 281);
        await test.step('merge an amendment', async () => {
            await expect(page.locator('ins')).toContainText('Zombie');

            await page.locator('#sidebar .mergeIntoMotion a').click();
            await page.waitForTimeout(1000);

            await page.locator('.save-row .goto_2').click();
            await page.waitForTimeout(1000);
            await expect(page.locator('.versionSelector').first()).toBeVisible();

            await expect(page.locator("input[name='version_2_2'][value='modified']")).toBeChecked();
        });

        await test.step('see the different versions', async () => {
            await expect(page.locator('.modifiedVersion.motionTextHolder ins')).toContainText('Zombie');
            await expect(page.locator('.originalVersion.motionTextHolder').filter({ visible: true })).toHaveCount(0);

            await page.evaluate(() => {
                const inp = document.querySelector(
                    "input[name=\"version_2_2\"][value=\"original\"]",
                ) as HTMLElement | null as HTMLInputElement | null;
                if (inp) {
                    inp.checked = true;
                    inp.click();
                }
            });
            await expect(page.locator('.modifiedVersion.motionTextHolder').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.originalVersion.motionTextHolder').first()).toBeVisible();

            await page.evaluate(() => {
                const inp = document.querySelector('.modifySelector input') as HTMLElement | null as HTMLInputElement | null;
                if (inp) inp.click();
            });
            await expect(page.locator('.originalVersion.modifyText').first()).toBeVisible();

            await page.evaluate(() => {
                const inp = document.querySelector(
                    "input[name=\"version_2_2\"][value=\"modified\"]",
                ) as HTMLElement | null as HTMLInputElement | null;
                if (inp) {
                    inp.checked = true;
                    inp.click();
                }
            });
            await expect(page.locator('.modifiedVersion.modifyText').first()).toBeVisible();

            await page.waitForTimeout(1000);
            await page.evaluate(() => {
                const w = window as any;
                const ed = w.CKEDITOR.instances.new_paragraphs_modified_2_2;
                ed.setData(ed.getData() + '<p>A modified adaption</p>');
            });

            await page.locator('.checkAmendmentCollisions').click();
            await page.waitForTimeout(1000);
            await expect(page.locator('.amendmentCollisionsHolder .alert-success').first()).toBeVisible();
            await page.locator('#amendmentMergeForm [name="save"]').click();
            await expect(page.locator('.alert-success')).toContainText(
                'Der Änderungsantrag wurde eingepflegt.',
            );
        });

        await test.step('check the changes were made', async () => {
            await page.locator('.alert-success .btn-primary').click();
            await expect(page.locator('h1')).toContainText('A8');
            await expect(page.locator('.motionDataTable .btnHistoryOpener').first()).toBeVisible();
            await dispatchClick(page, '.motionDataTable .btnHistoryOpener');
            await expect(page.locator('.motionDataTable .motionHistory a')).toContainText('Version 1');
            await expect(page.locator('body')).toContainText('A modified adaption');
            await expect(page.locator('body')).toContainText('Zombie ipsum');
        });
    });
});
