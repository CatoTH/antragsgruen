import { test, expect } from '../../fixtures';
import { loginAsProposalAdmin, loginAsStdAdmin, logout } from '../../utils/auth';
import { FIRST_FREE_TAG_ID } from '../../utils/constants';

test.describe('Proposed procedure: tags', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('add proposal-only tags and filter by them', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsProposalAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630');
        await expect(page.locator('#proposedChanges')).toHaveCount(0);
        await page.locator('.proposedChangesOpener button').click();
        await expect(page.locator('#proposedChanges')).toBeVisible();
        await page.locator('#proposedChanges').scrollIntoViewIfNeeded();
        await expect(page.locator('#proposedChanges .saving')).toHaveCount(0);

        await page.evaluate(() => {
            const inst = (window as any).$('.proposalTagsSelect')[0].selectize;
            inst.createItem('<pseudotag>');
            inst.createItem("Äöé\\'");
            inst.createItem('我爱你😀');
        });

        await expect(page.locator('#proposedChanges .saving')).toBeVisible();
        await expect(page.locator('#proposedChanges .saved')).toHaveCount(0);
        await page.locator('#proposedChanges .saving button').click();
        await page.waitForTimeout(500);
        await expect(page.locator('#proposedChanges .saved')).toBeVisible();

        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630/279');

        await expect(page.locator('#proposedChanges')).toBeVisible();
        await page.locator('#proposedChanges').scrollIntoViewIfNeeded();
        await expect(page.locator('#proposedChanges .saving')).toHaveCount(0);

        await page.evaluate(() => {
            const inst = (window as any).$('.proposalTagsSelect')[0].selectize;
            inst.createItem('Traffic');
            inst.createItem('我爱你😀');
        });

        await expect(page.locator('#proposedChanges .saving')).toBeVisible();
        await expect(page.locator('#proposedChanges .saved')).toHaveCount(0);
        await page.locator('#proposedChanges .saving button').click();
        await page.waitForTimeout(500);
        await page.locator('#proposedChanges').scrollIntoViewIfNeeded();
        await expect(page.locator('#proposedChanges .saved')).toBeVisible();

        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630');
        await expect(page.locator('.motionDataTable')).toContainText('Umwelt');
        await expect(page.locator('.motionDataTable')).not.toContainText('pseudotag');
        await expect(page.locator('#proposedChanges')).toContainText('pseudotag');

        await page.goto('/stdparteitag/std-parteitag');
        await page.goto('/stdparteitag/std-parteitag/motion/create');
        await expect(page.locator('body')).not.toContainText('pseudotag');

        await page.locator('#motionListLink').click();

        await expect(page.locator('.motion118 .tagsCol')).toContainText('<pseudotag>');
        await expect(page.locator('.motion118 .tagsCol')).toContainText("Äöé\\'");
        await expect(page.locator('.motion118 .tagsCol')).toContainText('我爱你😀');

        await expect(page.locator('.motion118')).toBeVisible();
        await expect(page.locator('.motion2')).toBeVisible();
        await expect(page.locator('.amendment279'.first())).toBeVisible();
        await expect(page.locator('.amendment280'.first())).toBeVisible();

        await page.locator('#filterSelectTags').selectOption(String(FIRST_FREE_TAG_ID + 2));
        await page.locator(".motionListSearchForm [name='search']").click();

        await expect(page.locator('.motion118')).toBeVisible();
        await expect(page.locator('.motion2')).toHaveCount(0);
        await expect(page.locator('.amendment279'.first())).toBeVisible();
        await expect(page.locator('.amendment280'.first())).toHaveCount(0);

        await page.locator('#exportProcedureBtn').click();
        await page.locator('.exportProcedureDd .linkProcedureIntern a').click();
        await expect(page.locator('.proposedProcedureOverview')).toBeVisible();

        await expect(page.locator('.tagList')).toContainText('我爱你😀');
        await expect(page.locator('.tagList')).toContainText('Traffic');
        await expect(page.locator('.tagList')).toContainText("Äöé\\'");
        await expect(page.locator('.tagList')).toContainText('<pseudotag>');
        await expect(page.locator('.tagList')).not.toContainText('Umwelt');

        await expect(page.locator('.motion118')).toBeVisible();
        await expect(page.locator('.motion2')).toBeVisible();
        await expect(page.locator('.amendment279'.first())).toBeVisible();
        await expect(page.locator('.amendment280'.first())).toBeVisible();

        await page.locator(`.tagList .tag${FIRST_FREE_TAG_ID + 2}`).click();

        await expect(page.locator('.motion118')).toBeVisible();
        await expect(page.locator('.motion2')).toHaveCount(0);
        await expect(page.locator('.amendment279'.first())).toBeVisible();
        await expect(page.locator('.amendment280'.first())).toHaveCount(0);

        await page.locator('.tagList .tagAll').click();

        await expect(page.locator('.motion118')).toBeVisible();
        await expect(page.locator('.motion2')).toBeVisible();
        await expect(page.locator('.amendment279'.first())).toBeVisible();
        await expect(page.locator('.amendment280'.first())).toBeVisible();

        await page.goto('/stdparteitag/std-parteitag');
        await logout(page);
        await loginAsStdAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630');
        await page.locator('#sidebar .adminEdit a').click();
        await expect(page.locator('.tagList')).toContainText('Umwelt');
        await expect(page.locator('.tagList')).not.toContainText('pseudotag');
        await expect(page.locator("input[name='tags[]'][value='1']")).toBeChecked();
        await expect(page.locator("input[name='tags[]'][value='2']")).not.toBeChecked();
    });
});
