import { test, expect } from '../../fixtures';
import { loginAsProposalAdmin, loginAsStdUser, loginAsConsultationAdmin, logout } from '../../utils/auth';
import { FIRST_FREE_VOTING_BLOCK_ID, FIRST_FREE_MOTION_ID } from '../../utils/constants';

test.describe('Proposed procedure: amendment edit workflow', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('full amendment proposed procedure workflow', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630/279');

        await expect(page.locator('#proposedChanges')).toHaveCount(0);
        await expect(page.locator('#proposedProcedureLink')).toHaveCount(0);

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('.motionRow118')).toBeVisible();
        await loginAsProposalAdmin(page);
        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630/279');

        await expect(page.locator('#proposedChanges')).toHaveCount(0);
        await page.locator('.proposedChangesOpener button').click();
        await expect(page.locator('#proposedChanges')).toBeVisible();

        await page.locator('#proposedChanges .proposalCommentForm textarea').fill('Internal comment!');
        await page.locator('#proposedChanges .proposalCommentForm .btnSubmit').click();
        await page.waitForTimeout(500);
        await expect(page.locator('#proposedChanges .proposalCommentForm .commentList')).toContainText('Internal comment!');

        await expect(
            page.locator(`#proposedChanges .proposalStatus6 input`),
        ).not.toBeChecked();
        await expect(page.locator('#proposedChanges .status_6')).toHaveCount(0);
        await expect(page.locator('#proposedChanges .saving')).toHaveCount(0);
        await page.evaluate(() => {
            const inp = document.querySelector(
                '#proposedChanges .proposalStatus6 input',
            ) as HTMLElement | null as HTMLInputElement | null;
            if (inp) {
                inp.checked = true;
                inp.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await page.waitForTimeout(100);
        await expect(page.locator('#proposedChanges .status_6')).toHaveCount(0);
        await expect(page.locator('#proposedChanges .saving')).toBeVisible();
        await page.locator('#proposedChanges .saving button').click();
        await page.waitForTimeout(1000);

        await expect(page.locator('#section_holder_2 ins')).toContainText('A small replacement');
        await page.evaluate(() => {
            const w = window as any;
            const data = w.CKEDITOR.instances.sections_2_wysiwyg.getData();
            w.CKEDITOR.instances.sections_2_wysiwyg.setData(data.replace(/A small/, 'A really small'));
        });
        await page.locator('#proposedChangeTextForm [name="save"]').click();
        await expect(page.locator('.alert-success')).toBeVisible();
        await page.waitForTimeout(1000);

        await expect(page.locator('#proposedChanges .status_6')).toBeVisible();
        await page.evaluate(() => {
            const inp = document.querySelector(
                '#proposedChanges input[name="proposalVisible"]',
            ) as HTMLElement | null as HTMLInputElement | null;
            if (inp) {
                inp.checked = true;
                inp.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await page.evaluate(() => {
            const sel = document.querySelector('#votingBlockId') as HTMLElement | null as HTMLSelectElement | null;
            if (sel) {
                sel.value = 'NEW';
                sel.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await page.locator('#newBlockTitle').fill('Voting 1');
        await expect(page.locator('#proposedChanges input[name="newVersion"]')).toHaveValue('current');
        await page.locator('#proposedChanges .saving button').click();
        await page.waitForTimeout(1000);
        await expect(page.locator('.proposalHistory')).toHaveCount(0);

        await expect(
            page.locator('#proposedChanges .notificationStatus'),
        ).toContainText('Über den Vorschlag informieren und Bestätigung einholen');
        await expect(page.locator('.notifyProposerSection')).toHaveCount(0);
        await page.locator('#proposedChanges button.notifyProposer').click();
        await page.waitForTimeout(1000);
        await expect(page.locator('.notifyProposerSection')).toBeVisible();
        const stdText = await page.locator('#proposedChanges textarea[name="proposalNotificationText"]').inputValue();
        await page
            .locator('#proposedChanges textarea[name="proposalNotificationText"]')
            .fill(`${stdText}\nADDITIONAL TEXT 123`);
        await page.locator('#proposedChanges button[name="notificationSubmit"]').click();
        await page.waitForTimeout(1000);
        await expect(page.locator('body')).toContainText('Der/die Antragsteller*in wurde am');
        await expect(page.locator('#proposedChanges .proposalCommentForm .commentList')).toContainText('ADDITIONAL TEXT 123');

        const selectedVoting = await page.evaluate(() => {
            const sel = document.querySelector('#votingBlockId') as HTMLSelectElement | null;
            if (!sel) return null;
            const opt = sel.options[sel.selectedIndex];
            return opt ? opt.text : null;
        });
        expect(selectedVoting).toBe('Voting 1');

        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630/280');
        await expect(page.locator('#proposedChanges .collision279')).toBeVisible();
        await page.evaluate(() => {
            const inp = document.querySelector(
                '#proposedChanges .proposalStatus5 input',
            ) as HTMLElement | null as HTMLInputElement | null;
            if (inp) {
                inp.checked = true;
                inp.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await page.locator('#proposedChanges .saving button').click();
        await page.waitForTimeout(1000);

        await expect(page.locator('body')).not.toContainText('Der/die Antragsteller*in wurde am');
        await expect(page.locator('.notifyProposerSection')).toHaveCount(0);
        await page.locator('#proposedChanges button.notifyProposer').click();
        await page.waitForTimeout(1000);
        await expect(page.locator('.notifyProposerSection')).toBeVisible();
        const stdText2 = await page.locator('#proposedChanges textarea[name="proposalNotificationText"]').inputValue();
        await page
            .locator('#proposedChanges textarea[name="proposalNotificationText"]')
            .fill(`${stdText2}\nADDITIONAL TEXT 123`);
        await page.locator('#proposedChanges button[name="notificationSubmit').click();
        await page.waitForTimeout(1000);
        await expect(page.locator('body')).toContainText('Der/die Antragsteller*in wurde am');
        await expect(page.locator('#proposedChanges .proposalCommentForm .commentList')).toContainText('ADDITIONAL TEXT 123');

        await page.goto('/stdparteitag/std-parteitag');
        await logout(page);
        await page.goto('/stdparteitag/std-parteitag/admin/index/appearance');
        await page.locator('#proposalProcedurePage').check();
        await page.locator('#consultationAppearanceForm [name="save"]').click();

        await page.goto('/stdparteitag/std-parteitag');
        await logout(page);
        await expect(page.locator('#proposedProcedureLink')).toBeVisible();
        await page.locator('#proposedProcedureLink').click();
        await expect(
            page.locator(`.votingTable${FIRST_FREE_VOTING_BLOCK_ID}`),
        ).toContainText('Voting 1');
        await expect(
            page.locator(`.votingTable${FIRST_FREE_VOTING_BLOCK_ID} .amendment279`),
        ).toBeVisible();
        await expect(
            page.locator(`.votingTable${FIRST_FREE_VOTING_BLOCK_ID} .amendment280`),
        ).toHaveCount(0);

        await loginAsStdUser(page);
        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630/280');
        await expect(page.locator('.agreeToProposal')).toBeVisible();
        await page.locator('.agreeToProposal textarea[name="comment"]').fill('No, disagree');
        await page.locator(".agreeToProposal [name='setProposalDisagree']").click();
        await expect(page.locator('.alert-success')).toBeVisible();
        await expect(page.locator('.agreeToProposal .commentList .disagreed')).toBeVisible();
        await expect(page.locator('.agreeToProposal .commentList')).toContainText('No, disagree');

        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630/279');
        await expect(page.locator('.agreeToProposal')).toBeVisible();
        await expect(page.locator('.agreeToProposal')).toContainText('ADDITIONAL TEXT 123');
        await page.locator(".agreeToProposal [name='setProposalAgree']").click();
        await expect(page.locator('.alert-success')).toBeVisible();

        await logout(page);
        await loginAsProposalAdmin(page);
        await expect(page.locator('.notificationSettings .accepted')).toBeVisible();
        await expect(page.locator('.notificationSettings .rejected')).toHaveCount(0);
        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630/280');
        await expect(page.locator('.notificationSettings .accepted')).toHaveCount(0);
        await expect(page.locator('.notificationSettings .rejected')).toBeVisible();

        await page.goto('/stdparteitag/std-parteitag/admin/motion-list');
        await expect(page.locator('.amendment279 .visible')).toBeVisible();
        await expect(page.locator('.amendment279 .notVisible')).toHaveCount(0);
        await expect(page.locator('.amendment280 .notVisible')).toBeVisible();
        await expect(page.locator('.amendment280 .visible')).toHaveCount(0);

        await page.locator('.amendment280 .selectbox').check();
        await page.locator(".motionListForm [name='proposalVisible']").click();
        await expect(page.locator('.amendment280 .notVisible')).toHaveCount(0);
        await expect(page.locator('.amendment280 .visible')).toBeVisible();

        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630');
        await expect(page.locator('.motionDataTable')).toContainText('Umwelt');
        await expect(page.locator('#sidebar .mergeamendments')).toHaveCount(0);

        await logout(page);
        await loginAsConsultationAdmin(page);
        await page.locator('#sidebar .mergeamendments a').click();
        await expect(page.locator('.amendment279 .textProposal input')).toBeChecked();
        await expect(page.locator('.amendment280 .textProposal')).toHaveCount(0);
        await page.locator('.amendment280 .colCheck input').uncheck();
        await page.locator('.mergeAllRow [type="submit"]').click();
        await page.waitForTimeout(1000);

        await expect(page.locator('#sections_2_1_wysiwyg ins')).toContainText('A really small replacement');
        await page.evaluate(() => {
            document.querySelectorAll('.none').forEach((el) => el.remove());
            document.querySelectorAll('#draftSavingPanel').forEach((el) => el.remove());
        });
        await page.locator('.motionMergeForm [name="save"]').click();
        await expect(page.locator('body')).toContainText('A really small replacement');
        await expect(page.locator('body')).not.toContainText('A big replacement');
        await page.locator('#motionConfirmForm [name="confirm"]').click();
        await page.locator('#motionConfirmedForm [type="submit"]').click();
        await expect(page.locator('body')).toContainText('A really small replacement');

        await expect(page.locator('h1')).toContainText('Testing proposed changes');
        await expect(page.locator('.motionDataTable .historyOpener .currVersion')).toContainText('Version 2');
        await page.locator('.motionDataTable .btnHistoryOpener').click();
        await expect(page.locator('.motionDataTable .motionHistory a')).toContainText('Version 1');
        await expect(page.locator('.motionDataTable')).toContainText('Umwelt');
        await page.goto('/stdparteitag/std-parteitag');
        await expect(
            page.locator(`.sectionResolutions .motionLink${FIRST_FREE_MOTION_ID + 1}`),
        ).toContainText('Testing proposed changes');
    });
});
