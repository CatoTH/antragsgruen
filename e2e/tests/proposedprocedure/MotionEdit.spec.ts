import { test, expect } from '../../fixtures';
import { loginAsProposalAdmin, loginAsStdUser, loginAsConsultationAdmin, logout } from '../../utils/auth';
import { FIRST_FREE_VOTING_BLOCK_ID, FIRST_FREE_MOTION_ID } from '../../utils/constants';
import { dispatchClick } from '../../utils/dom';

test.describe('Proposed procedure: motion edit workflow', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('full motion proposed procedure workflow', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsProposalAdmin(page);
        await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630');

        await expect(page.locator('#proposedChanges').filter({ visible: true })).toHaveCount(0);
        await dispatchClick(page, '.proposedChangesOpener button');
        await expect(page.locator('#proposedChanges').first()).toBeVisible();
        await expect(page.locator('#pp_section_2_0').filter({ visible: true })).toHaveCount(0);

        await test.step('write internal comments', async () => {
            await page.locator('#proposedChanges .proposalCommentForm textarea').first().fill('Internal comment!');
            await dispatchClick(page, '#proposedChanges .proposalCommentForm .btnSubmit');
            await page.waitForTimeout(1000);
            await expect(page.locator('#proposedChanges .proposalCommentForm .commentList')).toContainText('Internal comment!');

            await expect(
                page.locator('#proposedChanges .proposalStatus6 input'),
            ).not.toBeChecked();
        });

        await expect(page.locator('#proposedChanges .status_6').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#proposedChanges .saving').filter({ visible: true })).toHaveCount(0);
        await page.evaluate(() => {
            const inp = document.querySelector(
                '#proposedChanges .proposalStatus6 input',
            ) as HTMLElement | null as HTMLInputElement | null;
            if (inp) {
                inp.checked = true;
                inp.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await expect(page.locator('#proposedChanges .status_6').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#proposedChanges .saving').first()).toBeVisible();
        await dispatchClick(page, '#proposedChanges .saving button');
        await page.waitForTimeout(1000);

        await test.step('edit the modification', async () => {
            await expect(page.locator('#section_holder_2')).toContainText('Lorem ipsum dolor sit amet');
            await page.evaluate(() => {
                const w = window as any;
                const data = w.CKEDITOR.instances.sections_2_wysiwyg.getData();
                w.CKEDITOR.instances.sections_2_wysiwyg.setData(
                    data.replace(/Lorem ipsum dolor sit amet/, 'Vegetable ipsum dolor sit amet'),
                );
            });
            await page.locator('#proposedChangeTextForm [name="save"]').click();
            await expect(page.locator('.alert-success').first()).toBeVisible();
            await page.waitForTimeout(1000);
        });

        await test.step('make the proposal visible and notify the proposer of the amendment', async () => {
            await expect(page.locator('#proposedChanges .status_6').first()).toBeVisible();
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
            await page.locator('#newBlockTitle').first().fill('Voting 1');
            await expect(page.locator('#proposedChanges input[name="newVersion"]:checked')).toHaveValue('current');
            await dispatchClick(page, '#proposedChanges .saving button');
            await page.waitForTimeout(1000);
            await expect(page.locator('.proposalHistory').filter({ visible: true })).toHaveCount(0);

            await expect(
                page.locator('#proposedChanges .notificationStatus'),
            ).toContainText('Über den Vorschlag informieren und Bestätigung einholen');
            await expect(page.locator('.notifyProposerSection').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '#proposedChanges button.notifyProposer');
            await page.waitForTimeout(1000);
            await expect(page.locator('.notifyProposerSection').first()).toBeVisible();
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
        });

        await test.step('make the proposal page visible', async () => {
            await page.locator('#proposalProcedurePage').first().check();
            await page.locator('#consultationAppearanceForm [name="save"]').click();

            await page.goto('/stdparteitag/std-parteitag');
            await logout(page);
        });

        await test.step('see the proposal page', async () => {
            await expect(page.locator('#proposedProcedureLink').first()).toBeVisible();
            await page.locator('#proposedProcedureLink').click();
            await expect(
                page.locator(`.votingTable${FIRST_FREE_VOTING_BLOCK_ID}`),
            ).toContainText('Voting 1');
            await expect(
                page.locator(`.votingTable${FIRST_FREE_VOTING_BLOCK_ID} .motion118`),
            ).toBeVisible();

            await loginAsStdUser(page);
            await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630');
        });

        await test.step('accidentally disagree with the proposal', async () => {
            await expect(page.locator('#pp_section_2_0 ins')).toContainText('Vegetable');
            await expect(page.locator('.agreeToProposal')).toContainText('ADDITIONAL TEXT 123');
            await page.locator('.agreeToProposal textarea[name="comment"]').first().fill('Yes, but with wrong button');
            await page.locator(".agreeToProposal [name='setProposalDisagree']").click();
            await expect(page.locator('.alert-success').first()).toBeVisible();
            await expect(page.locator('.agreeToProposal .commentList .disagreed').first()).toBeVisible();
            await expect(page.locator('.agreeToProposal .commentList .agreed').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.agreeToProposal .commentList')).toContainText('Yes, but with wrong button');
        });

        await test.step('redo my decision', async () => {
            await dispatchClick(page, '.agreeToProposal .btnUpdateDecision');
            await page.locator('.agreeToProposal textarea[name="comment"]').first().fill('Yes, this time for real');
            await page.locator(".agreeToProposal [name='setProposalAgree']").click();
            await expect(page.locator('.alert-success').first()).toBeVisible();
            await expect(page.locator('.agreeToProposal .commentList .disagreed').first()).toBeVisible();
            await expect(page.locator('.agreeToProposal .commentList .agreed').first()).toBeVisible();
            await expect(page.locator('.agreeToProposal .commentList')).toContainText('Yes, this time for real');

            await logout(page);
            await loginAsProposalAdmin(page);
        });

        await test.step('see the agreement as admin', async () => {
            await expect(page.locator('.notificationSettings .accepted').first()).toBeVisible();
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
});
