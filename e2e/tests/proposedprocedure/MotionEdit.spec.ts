import { test, expect } from '../../fixtures';
import { loginAsProposalAdmin, loginAsStdUser, loginAsConsultationAdmin, logout } from '../../utils/auth';
import { FIRST_FREE_VOTING_BLOCK_ID, FIRST_FREE_MOTION_ID } from '../../utils/constants';

test.describe('Proposed procedure: motion edit workflow', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('full motion proposed procedure workflow', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsProposalAdmin(page);
        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630');

        await expect(page.locator('#proposedChanges')).toHaveCount(0);
        await page.locator('.proposedChangesOpener button').click();
        await expect(page.locator('#proposedChanges')).toBeVisible();
        await expect(page.locator('#pp_section_2_0')).toHaveCount(0);

        await page.locator('#proposedChanges .proposalCommentForm textarea').fill('Internal comment!');
        await page.locator('#proposedChanges .proposalCommentForm .btnSubmit').click();
        await page.waitForTimeout(1000);
        await expect(page.locator('#proposedChanges .proposalCommentForm .commentList')).toContainText('Internal comment!');

        await expect(
            page.locator('#proposedChanges .proposalStatus6 input'),
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
        await expect(page.locator('#proposedChanges .status_6')).toHaveCount(0);
        await expect(page.locator('#proposedChanges .saving')).toBeVisible();
        await page.locator('#proposedChanges .saving button').click();
        await page.waitForTimeout(1000);

        await expect(page.locator('#section_holder_2')).toContainText('Lorem ipsum dolor sit amet');
        await page.evaluate(() => {
            const w = window as any;
            const data = w.CKEDITOR.instances.sections_2_wysiwyg.getData();
            w.CKEDITOR.instances.sections_2_wysiwyg.setData(
                data.replace(/Lorem ipsum dolor sit amet/, 'Vegetable ipsum dolor sit amet'),
            );
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

        await page.goto('/stdparteitag/std-parteitag');
        await logout(page);
        await page.goto('/stdparteitag/std-parteitag/admin/appearance');
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
            page.locator(`.votingTable${FIRST_FREE_VOTING_BLOCK_ID} .motion118`),
        ).toBeVisible();

        await loginAsStdUser(page);
        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630');
        await expect(page.locator('#pp_section_2_0 ins')).toContainText('Vegetable');
        await expect(page.locator('.agreeToProposal')).toContainText('ADDITIONAL TEXT 123');
        await page.locator('.agreeToProposal textarea[name="comment"]').fill('Yes, but with wrong button');
        await page.locator(".agreeToProposal [name='setProposalDisagree']").click();
        await expect(page.locator('.alert-success')).toBeVisible();
        await expect(page.locator('.agreeToProposal .commentList .disagreed')).toBeVisible();
        await expect(page.locator('.agreeToProposal .commentList .agreed')).toHaveCount(0);
        await expect(page.locator('.agreeToProposal .commentList')).toContainText('Yes, but with wrong button');

        await page.locator('.agreeToProposal .btnUpdateDecision').click();
        await page.locator('.agreeToProposal textarea[name="comment"]').fill('Yes, this time for real');
        await page.locator(".agreeToProposal [name='setProposalAgree']").click();
        await expect(page.locator('.alert-success')).toBeVisible();
        await expect(page.locator('.agreeToProposal .commentList .disagreed')).toBeVisible();
        await expect(page.locator('.agreeToProposal .commentList .agreed')).toBeVisible();
        await expect(page.locator('.agreeToProposal .commentList')).toContainText('Yes, this time for real');

        await logout(page);
        await loginAsProposalAdmin(page);
        await expect(page.locator('.notificationSettings .accepted')).toBeVisible();
        await expect(page.locator('#proposedChanges .commentList')).toContainText('Yes, but with wrong button');
        await expect(page.locator('#proposedChanges .commentList')).toContainText('Yes, this time for real');

        await logout(page);
        await loginAsConsultationAdmin(page);
        await page.locator('#sidebar .mergeamendments a').click();
        await page.locator('.mergeAllRow [type="submit"]').click();
        await page.waitForTimeout(1000);

        await expect(page.locator('#sections_2_0_wysiwyg ins')).toContainText('Vegetable');
        await page.evaluate(() => {
            document.querySelectorAll('.none').forEach((el) => el.remove());
            document.querySelectorAll('#draftSavingPanel').forEach((el) => el.remove());
        });
        await page.locator('.motionMergeForm [name="save"]').click();
        await expect(page.locator('body')).toContainText('Vegetable');
        await page.locator('#motionConfirmForm [name="confirm"]').click();
        await page.locator('#motionConfirmedForm [type="submit"]').click();
        await expect(page.locator('body')).toContainText('Vegetable');

        await expect(page.locator('h1')).toContainText('Testing proposed changes');
        await expect(page.locator('.motionHistory')).toContainText('Version 2');
        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator(`.motionLink${FIRST_FREE_MOTION_ID + 1}`)).toContainText('Testing proposed changes');
    });
});
