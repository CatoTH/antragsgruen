import { test, expect } from '../../fixtures';
import { loginAsProposalAdmin, loginAsStdAdmin, logout } from '../../utils/auth';
import { FIRST_FREE_TAG_ID } from '../../utils/constants';
import { gotoAmendment } from '../../utils/navigation';
import { dispatchClick } from '../../utils/dom';

test.describe('Proposed procedure: tags', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('add proposal-only tags and filter by them', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsProposalAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630');
        await test.step('set some tags', async () => {
            await expect(page.locator('#proposedChanges').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '.proposedChangesOpener button');
            await expect(page.locator('#proposedChanges').first()).toBeVisible();
            await page.locator('#proposedChanges').scrollIntoViewIfNeeded();
            await expect(page.locator('#proposedChanges .saving').filter({ visible: true })).toHaveCount(0);

            // One round-trip each, as the Cept does: selectize's createItem() is asynchronous,
            // and firing them back-to-back in a single evaluate loses all but the first.
            for (const tag of ['<pseudotag>', "Äöé\\'", '我爱你😀']) {
                await page.evaluate(
                    (name) => (window as any).$('.proposalTagsSelect')[0].selectize.createItem(name),
                    tag,
                );
            }

            await expect(page.locator('#proposedChanges .saving').first()).toBeVisible();
            await expect(page.locator('#proposedChanges .saved').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '#proposedChanges .saving button');
            await page.waitForTimeout(500);
            await expect(page.locator('#proposedChanges .saved').first()).toBeVisible();

            await gotoAmendment(page, true, 'Testing_proposed_changes-630', 279);

            await expect(page.locator('#proposedChanges').first()).toBeVisible();
            await page.locator('#proposedChanges').scrollIntoViewIfNeeded();
            await expect(page.locator('#proposedChanges .saving').filter({ visible: true })).toHaveCount(0);

            for (const tag of ['Traffic', '我爱你😀']) {
                await page.evaluate(
                    (name) => (window as any).$('.proposalTagsSelect')[0].selectize.createItem(name),
                    tag,
                );
            }

            await expect(page.locator('#proposedChanges .saving').first()).toBeVisible();
            await expect(page.locator('#proposedChanges .saved').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '#proposedChanges .saving button');
            await page.waitForTimeout(500);
            await page.locator('#proposedChanges').scrollIntoViewIfNeeded();
            await expect(page.locator('#proposedChanges .saved').first()).toBeVisible();

            await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630');
            await expect(page.locator('.motionDataTable')).toContainText('Umwelt');
            await expect(page.locator('.motionDataTable').getByText('pseudotag').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#proposedChanges')).toContainText('pseudotag');

            await page.goto('/stdparteitag/std-parteitag');
            await page.goto('/stdparteitag/std-parteitag/motion/create');
            await expect(page.locator('body')).not.toContainText('pseudotag', { useInnerText: true });
        });

        await test.step('test the filter in the motion list', async () => {
            await page.locator('#motionListLink').click();

            await expect(page.locator('.motion118 .tagsCol')).toContainText('<pseudotag>');
            await expect(page.locator('.motion118 .tagsCol')).toContainText("Äöé\\'");
            await expect(page.locator('.motion118 .tagsCol')).toContainText('我爱你😀');

            await expect(page.locator('.motion118').first()).toBeVisible();
            await expect(page.locator('.motion2').first()).toBeVisible();
            await expect(page.locator('.amendment279').first()).toBeVisible();
            await expect(page.locator('.amendment280').first()).toBeVisible();

            await page.locator('#filterSelectTags').first().selectOption(String(FIRST_FREE_TAG_ID + 2));
            await page.locator(".motionListSearchForm [name='search']").click();

            await expect(page.locator('.motion118').first()).toBeVisible();
            await expect(page.locator('.motion2').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.amendment279').first()).toBeVisible();
            await expect(page.locator('.amendment280').first()).not.toBeVisible();
        });

        await test.step('test the filter in the proposed procedure list', async () => {
            await page.locator('#exportProcedureBtn').click();
            await page.locator('.exportProcedureDd .linkProcedureIntern a').click();
            await expect(page.locator('.proposedProcedureOverview').first()).toBeVisible();

            await expect(page.locator('.tagList')).toContainText('我爱你😀');
            await expect(page.locator('.tagList')).toContainText('Traffic');
            await expect(page.locator('.tagList')).toContainText("Äöé\\'");
            await expect(page.locator('.tagList')).toContainText('<pseudotag>');
            await expect(page.locator('.tagList').getByText('Umwelt').filter({ visible: true })).toHaveCount(0);

            await expect(page.locator('.motion118').first()).toBeVisible();
            await expect(page.locator('.motion2').first()).toBeVisible();
            await expect(page.locator('.amendment279').first()).toBeVisible();
            await expect(page.locator('.amendment280').first()).toBeVisible();

            await page.locator(`.tagList .tag${FIRST_FREE_TAG_ID + 2}`).click();

            await expect(page.locator('.motion118').first()).toBeVisible();
            await expect(page.locator('.motion2').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.amendment279').first()).toBeVisible();
            await expect(page.locator('.amendment280').first()).not.toBeVisible();

            await page.locator('.tagList .tagAll').click();

            await expect(page.locator('.motion118').first()).toBeVisible();
            await expect(page.locator('.motion2').first()).toBeVisible();
            await expect(page.locator('.amendment279').first()).toBeVisible();
            await expect(page.locator('.amendment280').first()).toBeVisible();

            await page.goto('/stdparteitag/std-parteitag');
            await logout(page);
            await loginAsStdAdmin(page);

            await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630');
        });

        await test.step('test as a regular admin', async () => {
            await page.locator('#sidebar .adminEdit a').click();
            await expect(page.locator('.tagList')).toContainText('Umwelt');
            await expect(page.locator('.tagList').getByText('pseudotag').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator("input[name='tags[]'][value='1']")).toBeChecked();
            await expect(page.locator("input[name='tags[]'][value='2']")).not.toBeChecked();
        });
    });
});
