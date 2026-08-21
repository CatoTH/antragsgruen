import { test, expect } from '../../fixtures';
import { loginAsProposalAdmin, loginAsStdUser, logout } from '../../utils/auth';

test.describe('Proposed procedure: motion versions', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create, switch, and delete motion-level proposal versions', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsProposalAdmin(page);
        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630');

        await page.locator('.proposedChangesOpener button').click();
        await expect(page.locator('#proposedChanges')).toBeVisible();
        await expect(page.locator('#proposedChanges #statusCustomStr')).toHaveCount(0);
        await page.locator('#proposedChanges .proposalStatus23').check();
        await expect(page.locator('#proposedChanges #statusCustomStr')).toBeVisible();
        await expect(page.locator('#proposedChanges input[name="newVersion"]')).toHaveValue('current');
        await page.locator('#proposedChanges #statusCustomStr').fill('Under review');
        await expect(page.locator('#proposedChanges .saving')).toBeVisible();
        await page.locator('#proposedChanges .saving button').click();
        await page.waitForTimeout(1000);

        await page.locator('#proposedChanges button.notifyProposer').click();
        await page.waitForTimeout(500);
        await expect(page.locator('.notifyProposerSection')).toBeVisible();
        await page.locator('#proposedChanges button[name="notificationSubmit"]').click();
        await page.waitForTimeout(1000);
        await expect(page.locator('body')).toContainText('Der/die Antragsteller*in wurde am');

        await logout(page);
        await loginAsStdUser(page);
        await page.locator(".agreeToProposal [name='setProposalDisagree']").click();
        await expect(page.locator('.alert-success')).toBeVisible();

        await logout(page);
        await loginAsProposalAdmin(page);
        await page.locator('#proposedChanges .proposalCommentForm textarea').fill('Internal comment!');
        await page.locator('#proposedChanges .proposalCommentForm button').click();
        await page.waitForTimeout(1000);
        await expect(page.locator('#proposedChanges .proposalCommentForm .commentList')).toContainText('Internal comment!');
        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630');
        await expect(page.locator('#proposedChanges #statusCustomStr')).toHaveValue('Under review');

        await expect(page.locator('#proposedChanges input[name="proposalStatus"]')).toHaveValue('23');
        await expect(page.locator('#proposedChanges .status_23')).toBeVisible();
        await page.evaluate(() => {
            const inp = document.querySelector(
                '#proposedChanges .proposalStatus4 input',
            ) as HTMLElement | null as HTMLInputElement | null;
            if (inp) {
                inp.checked = true;
                inp.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        const selected = await page.evaluate(() => {
            const inp = document.querySelector(
                '#proposedChanges input[name="newVersion"]:checked',
            ) as HTMLElement | null as HTMLInputElement | null;
            return inp?.value ?? null;
        });
        expect(selected).toBe('new');
        await page.evaluate(() => {
            const inp = document.querySelector(
                '#proposedChanges input[name="newVersion"]',
            ) as HTMLElement | null as HTMLInputElement | null;
            if (inp) inp.dispatchEvent(new Event('change', { bubbles: true }));
        });
        await expect(page.locator('#proposedChanges .saving')).toBeVisible();
        await page.locator('#proposedChanges .saving button').click();
        await page.waitForTimeout(1000);

        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630');
        await expect(page.locator('#proposedChanges input[name="proposalStatus"]')).toHaveValue('4');
        await expect(page.locator('#proposedChanges .proposalHistory')).toBeVisible();
        await page.locator('#proposedChanges .version1').click();
        await expect(page.locator('#proposedChanges input[name="proposalStatus"]')).toHaveValue('23');
        await expect(page.locator('#proposedChanges .notificationStatus .rejected')).toBeVisible();

        await logout(page);
        await loginAsStdUser(page);
        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630');
        await expect(page.locator('.agreeToProposal')).toContainText('Under review');
        await expect(page.locator('.agreeToProposal .agreement .disagreed')).toBeVisible();
        await expect(page.locator('.agreeToProposal .updateDecision')).toBeVisible();

        await logout(page);
        await loginAsProposalAdmin(page);
        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630');
        await expect(page.locator('#proposedChanges .proposalHistory')).toBeVisible();
        await expect(page.locator('#proposedChanges input[name="proposalStatus"]')).toHaveValue('4');
        await page.locator('#proposedChanges .btnDeleteProposal').click();
        await page.locator('.bootbox').waitFor();
        await expect(page.locator('.bootbox')).toContainText('wirklich gelöscht werden');
        await page.locator('.bootbox .btn-primary').click();
        await page.waitForTimeout(1000);
        await expect(page.locator('#proposedChanges .proposalHistory')).toHaveCount(0);
        await expect(page.locator('#proposedChanges input[name="proposalStatus"]')).toHaveValue('23');
    });
});
