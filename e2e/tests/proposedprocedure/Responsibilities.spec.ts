import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Proposed procedure: responsibilities', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('set and clear responsibilities on motions and amendments', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.goto('/stdparteitag/std-parteitag/admin/motion-list');

        await test.step('activate responsibilities', async () => {
            await expect(page.locator('.responsibilityCol').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.activateResponsibilities').filter({ visible: true })).toHaveCount(0);
            await page.locator('#activateFncBtn').click();
            await expect(page.locator('.activateResponsibilities').first()).toBeVisible();
            await page.locator('.activateResponsibilities').click();
            await expect(page.locator('.responsibilityCol').first()).toBeVisible();
            await expect(page.locator('.alert-success').first()).toBeVisible();
            await expect(page.locator('.filterResponsibility').filter({ visible: true })).toHaveCount(0);
        });

        await test.step('set a responsibility for a motion', async () => {
            await expect(page.locator('.motion3 .responsibilityCol .dropdown-menu').filter({ visible: true })).toHaveCount(0);
            await page.locator('.motion3 .responsibilityCol .respButton').click();
            await expect(page.locator('.motion3 .responsibilityCol .dropdown-menu').first()).toBeVisible();
            await expect(page.locator('.motion3 .responsibilityCol .respUserNone.selected').first()).toBeVisible();
            await page.locator('.motion3 .responsibilityCol .respUser8').click();
            await page.waitForTimeout(500);
            await expect(page.locator('.motion3 .responsibilityCol .dropdown-menu').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.motion3 .responsibilityUser')).toContainText('Proposal Admin');

            await page.locator('.motion3 .responsibilityCol .respButton').click();
            await expect(page.locator('.motion3 .responsibilityCol .dropdown-menu').first()).toBeVisible();
            await expect(page.locator('.motion3 .responsibilityCol .respUser8.selected').first()).toBeVisible();
            await page.locator('#respCommmotion3').first().fill('who else?');
            await page.locator('.motion3 .responsibilityCol .respCommentRow button').click();
            await page.waitForTimeout(500);
            await expect(page.locator('.motion3 .responsibilityCol .dropdown-menu').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.motion3 .responsibilityUser')).toContainText('Proposal Admin');
            await expect(page.locator('.motion3 .responsibilityComment')).toContainText('who else?');
        });

        await test.step('set a responsibility for an amendment', async () => {
            await expect(page.locator('.amendment3 .responsibilityCol .dropdown-menu').filter({ visible: true })).toHaveCount(0);
            await page.locator('.amendment3 .responsibilityCol .respButton').click();
            await expect(page.locator('.amendment3 .responsibilityCol .dropdown-menu').first()).toBeVisible();
            await expect(page.locator('.amendment3 .responsibilityCol .respUserNone.selected').first()).toBeVisible();
            await page.locator('.amendment3 .responsibilityCol .respUser7').click();
            await page.waitForTimeout(500);
            await expect(page.locator('.amendment3 .responsibilityCol .dropdown-menu').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.amendment3 .responsibilityUser')).toContainText('Single-Consultation Admin');

            await page.locator('.amendment3 .responsibilityCol .respButton').click();
            await expect(page.locator('.amendment3 .responsibilityCol .dropdown-menu').first()).toBeVisible();
            await expect(page.locator('.amendment3 .responsibilityCol .respUser7.selected').first()).toBeVisible();
            await page.locator('#respCommamendment3').first().fill("It's your turn");
            await page.locator('.amendment3 .responsibilityCol .respCommentRow button').click();
            await page.waitForTimeout(500);
            await expect(page.locator('.amendment3 .responsibilityCol .dropdown-menu').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.amendment3 .responsibilityUser')).toContainText('Single-Consultation Admin');
            await expect(page.locator('.amendment3 .responsibilityComment')).toContainText("It's your turn");
        });

        await test.step('see it in the proposed procedure', async () => {
            await page.locator('#exportProcedureBtn').click();
            await page.locator('.exportProcedureDd .linkProcedureIntern a').click();
            await expect(page.locator('.proposedProcedureOverview').first()).toBeVisible();
            await expect(page.locator('.motion3 .responsibilityCol')).toContainText('Proposal Admin');
            await expect(page.locator('.motion3 .responsibilityCol')).toContainText('who else?');
        });

        await test.step('reset it to zero', async () => {
            await page.locator('.motion3 .responsibilityCol .respButton').click();
            await page.locator('#respCommmotion3').first().fill('');
            await page.locator('.motion3 .responsibilityCol .respCommentRow button').click();
            await page.waitForTimeout(500);
            await page.locator('.motion3 .responsibilityCol .respButton').click();
            await page.locator('.motion3 .responsibilityCol .respUserNone').click();
            await page.waitForTimeout(500);
            await expect(page.locator('.motion3 .responsibilityCol .dropdown-menu').filter({ visible: true })).toHaveCount(0);

            const user = await page.evaluate(() => {
                return (document.querySelector('.motion3 .responsibilityUser') as HTMLElement | null)?.textContent ?? '';
            });
            expect(user).toBe('');
            const comment = await page.evaluate(() => {
                return (document.querySelector('.motion3 .responsibilityComment') as HTMLElement | null)?.textContent ?? '';
            });
            expect(comment).toBe('');
        });

        await test.step('check the filter', async () => {
            await page.locator('#motionListLink').click();
            await expect(page.locator('.filterResponsibility').first()).toBeVisible();
            await expect(page.locator('.motion2').first()).toBeVisible();
            await expect(page.locator('.amendment3').first()).toBeVisible();
            await page.locator('.filterResponsibility select').first().selectOption('7');
            await page.locator(".motionListSearchForm [type='submit']").click();
            await expect(page.locator('.motion2').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.amendment3').first()).toBeVisible();
        });
    });
});
