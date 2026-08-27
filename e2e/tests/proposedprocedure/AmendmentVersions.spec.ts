import { test, expect } from '../../fixtures';
import { loginAsProposalAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { gotoAmendment } from '../../utils/navigation';
import { dispatchClick } from '../../utils/dom';

test.describe('Proposed procedure: amendment versions', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create, switch, and delete proposal versions', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsProposalAdmin(page);
        await gotoAmendment(page, true, 'Testing_proposed_changes-630', 279);

        await dispatchClick(page, '.proposedChangesOpener button');
        await expect(page.locator('#proposedChanges').first()).toBeVisible();
        await expect(page.locator('#proposedChanges #statusCustomStr').filter({ visible: true })).toHaveCount(0);
        await page.evaluate(() => {
            const inp = document.querySelector(
                '#proposedChanges .proposalStatus23 input',
            ) as HTMLElement | null as HTMLInputElement | null;
            if (inp) {
                inp.checked = true;
                inp.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await expect(page.locator('#proposedChanges #statusCustomStr').first()).toBeVisible();
        await expect(page.locator('#proposedChanges input[name="newVersion"]:checked')).toHaveValue('current');
        await page.locator('#proposedChanges #statusCustomStr').first().fill('Under review');
        await expect(page.locator('#proposedChanges .saving').first()).toBeVisible();
        await dispatchClick(page, '#proposedChanges .saving button');
        await page.waitForTimeout(1000);

        await test.step('notify the initiator of the motion', async () => {
            await dispatchClick(page, '#proposedChanges button.notifyProposer');
            await page.waitForTimeout(500);
            await expect(page.locator('.notifyProposerSection').first()).toBeVisible();
            await page.locator('#proposedChanges button[name="notificationSubmit"]').click();
            await page.waitForTimeout(1000);
            await expect(page.locator('body')).toContainText('Der/die Antragsteller*in wurde am');

            await logout(page);
            await loginAsStdUser(page);
        });

        await test.step('reject the proposal as the user', async () => {
            await page.locator(".agreeToProposal [name='setProposalDisagree']").click();
            await expect(page.locator('.alert-success').first()).toBeVisible();

            await logout(page);
            await loginAsProposalAdmin(page);
        });

        await test.step('comment as admin, no new version created', async () => {
            await page.locator('#proposedChanges .proposalCommentForm textarea').first().fill('Internal comment!');
            await dispatchClick(page, '#proposedChanges .proposalCommentForm button');
            await page.waitForTimeout(1000);
            await expect(page.locator('#proposedChanges .proposalCommentForm .commentList')).toContainText('Internal comment!');
            await gotoAmendment(page, true, 'Testing_proposed_changes-630', 279);
            await expect(page.locator('#proposedChanges #statusCustomStr')).toHaveValue('Under review');

            await expect(page.locator('#proposedChanges input[name="proposalStatus"]:checked')).toHaveValue('23');
        });

        await test.step('create a new version', async () => {
            await expect(page.locator('#proposedChanges .status_23').first()).toBeVisible();
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
            await expect(page.locator('#proposedChanges .saving').first()).toBeVisible();
            await dispatchClick(page, '#proposedChanges .saving button');
            await page.waitForTimeout(1000);

            await gotoAmendment(page, true, 'Testing_proposed_changes-630', 279);
            await expect(page.locator('#proposedChanges input[name="proposalStatus"]:checked')).toHaveValue('4');
        });

        await test.step('see the old version', async () => {
            await expect(page.locator('#proposedChanges .proposalHistory').first()).toBeVisible();
            await page.locator('#proposedChanges a.version1').click();
            await page.waitForTimeout(1000);
            await expect(page.locator('#proposedChanges .status_23').first()).toBeVisible();
            await expect(page.locator('#proposedChanges input[name="proposalStatus"]:checked')).toHaveValue('23');
            await expect(page.locator('#proposedChanges .notificationStatus .rejected').first()).toBeVisible();

            await logout(page);
            await loginAsStdUser(page);
            await gotoAmendment(page, true, 'Testing_proposed_changes-630', 279);
        });

        await test.step('only see version 1 as user', async () => {
            await expect(page.locator('.agreeToProposal')).toContainText('Under review');
            await expect(page.locator('.agreeToProposal .agreement .disagreed').first()).toBeVisible();
            await expect(page.locator('.agreeToProposal .updateDecision').first()).toBeVisible();

            await logout(page);
            await loginAsProposalAdmin(page);
            await gotoAmendment(page, true, 'Testing_proposed_changes-630', 279);
        });

        await test.step('delete the newest proposal version as admin', async () => {
            await expect(page.locator('#proposedChanges .proposalHistory').first()).toBeVisible();
            await expect(page.locator('#proposedChanges input[name="proposalStatus"]:checked')).toHaveValue('4');
            await dispatchClick(page, '#proposedChanges .btnDeleteProposal');
            await page.locator('.bootbox').waitFor();
            await expect(page.locator('.bootbox')).toContainText('wirklich gelöscht werden');
            await page.locator('.bootbox .btn-primary').click();
            await page.waitForTimeout(1000);
            await expect(page.locator('#proposedChanges .proposalHistory').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#proposedChanges input[name="proposalStatus"]:checked')).toHaveValue('23');
        });

    });
});
