import { test, expect } from '../../fixtures';
import { loginAsProposalAdmin, loginAsStdUser, loginAsConsultationAdmin, logout } from '../../utils/auth';
import { FIRST_FREE_VOTING_BLOCK_ID, FIRST_FREE_MOTION_ID } from '../../utils/constants';
import { gotoAmendment } from '../../utils/navigation';
import { dispatchClick } from '../../utils/dom';

test.describe('Proposed procedure: amendment edit workflow', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('full amendment proposed procedure workflow', async ({ page }) => {
        await gotoAmendment(page, true, 'Testing_proposed_changes-630', 279);

        await expect(page.locator('#proposedChanges').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#proposedProcedureLink').filter({ visible: true })).toHaveCount(0);

        await page.goto('/stdparteitag/std-parteitag');
        await test.step('log in', async () => {
            await expect(page.locator('.motionRow118').first()).toBeVisible();
            await loginAsProposalAdmin(page);
            await gotoAmendment(page, true, 'Testing_proposed_changes-630', 279);

            await expect(page.locator('#proposedChanges').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '.proposedChangesOpener button');
            await expect(page.locator('#proposedChanges').first()).toBeVisible();
        });

        await test.step('write internal comments', async () => {
            await page.locator('#proposedChanges .proposalCommentForm textarea').first().fill('Internal comment!');
            await dispatchClick(page, '#proposedChanges .proposalCommentForm .btnSubmit');
            await page.waitForTimeout(500);
            await expect(page.locator('#proposedChanges .proposalCommentForm .commentList')).toContainText('Internal comment!');

            await expect(
                page.locator(`#proposedChanges .proposalStatus6 input`),
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
        await page.waitForTimeout(100);
        await expect(page.locator('#proposedChanges .status_6').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#proposedChanges .saving').first()).toBeVisible();
        await dispatchClick(page, '#proposedChanges .saving button');
        await page.waitForTimeout(1000);

        await test.step('edit the modification', async () => {
            await expect(page.locator('#section_holder_2 ins')).toContainText('A small replacement');
            await page.evaluate(() => {
                const w = window as any;
                const data = w.CKEDITOR.instances.sections_2_wysiwyg.getData();
                w.CKEDITOR.instances.sections_2_wysiwyg.setData(data.replace(/A small/, 'A really small'));
            });
            await page.locator('#proposedChangeTextForm [name="save"]').click();
            await expect(page.locator('.alert-success').first()).toBeVisible();
            await page.waitForTimeout(1000);
        });

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

        await gotoAmendment(page, true, 'Testing_proposed_changes-630', 280);
        await test.step('propose to reject the second amendment', async () => {
            await expect(page.locator('#proposedChanges .collision279').first()).toBeVisible();
            await page.evaluate(() => {
                const inp = document.querySelector(
                    '#proposedChanges .proposalStatus5 input',
                ) as HTMLElement | null as HTMLInputElement | null;
                if (inp) {
                    inp.checked = true;
                    inp.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
            await dispatchClick(page, '#proposedChanges .saving button');
            await page.waitForTimeout(1000);

            await expect(page.locator('body')).not.toContainText('Der/die Antragsteller*in wurde am', { useInnerText: true });
            await expect(page.locator('.notifyProposerSection').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '#proposedChanges button.notifyProposer');
            await page.waitForTimeout(1000);
            await expect(page.locator('.notifyProposerSection').first()).toBeVisible();
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
                page.locator(`.votingTable${FIRST_FREE_VOTING_BLOCK_ID} .amendment279`),
            ).toBeVisible();
            await expect(
                page.locator(`.votingTable${FIRST_FREE_VOTING_BLOCK_ID} .amendment280`),
            ).not.toBeVisible();

            await loginAsStdUser(page);
            await gotoAmendment(page, true, 'Testing_proposed_changes-630', 280);
        });

        await test.step('Disagree to one propsal', async () => {
            await expect(page.locator('.agreeToProposal').first()).toBeVisible();
        });

        await test.step('Agree to the second one', async () => {
            await page.locator('.agreeToProposal textarea[name="comment"]').first().fill('No, disagree');
            await page.locator(".agreeToProposal [name='setProposalDisagree']").click();
            await expect(page.locator('.alert-success').first()).toBeVisible();
            await expect(page.locator('.agreeToProposal .commentList .disagreed').first()).toBeVisible();
            await expect(page.locator('.agreeToProposal .commentList')).toContainText('No, disagree');

            await gotoAmendment(page, true, 'Testing_proposed_changes-630', 279);
            await expect(page.locator('.agreeToProposal').first()).toBeVisible();
            await expect(page.locator('.agreeToProposal')).toContainText('ADDITIONAL TEXT 123');
            await page.locator(".agreeToProposal [name='setProposalAgree']").click();
            await expect(page.locator('.alert-success').first()).toBeVisible();

            await logout(page);
            await loginAsProposalAdmin(page);
        });

        await test.step('see the agreement / disagreement as admin', async () => {
            await expect(page.locator('.notificationSettings .accepted').first()).toBeVisible();
            await expect(page.locator('.notificationSettings .rejected').filter({ visible: true })).toHaveCount(0);
            await gotoAmendment(page, true, 'Testing_proposed_changes-630', 280);
            await expect(page.locator('.notificationSettings .accepted').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.notificationSettings .rejected').first()).toBeVisible();

            await page.goto('/stdparteitag/std-parteitag/admin/motion-list');
        });

        await test.step('test the motion list', async () => {
            await expect(page.locator('.amendment279 .visible').first()).toBeVisible();
            await expect(page.locator('.amendment279 .notVisible').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.amendment280 .notVisible').first()).toBeVisible();
            await expect(page.locator('.amendment280 .visible').filter({ visible: true })).toHaveCount(0);
        });

        await test.step('make the second proposal visible', async () => {
            await page.locator('.amendment280 .selectbox').first().check();
            await page.locator(".motionListForm [name='proposalVisible']").click();
            await expect(page.locator('.amendment280 .notVisible').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.amendment280 .visible').first()).toBeVisible();

            await page.goto('/stdparteitag/std-parteitag/motion/Testing_proposed_changes-630');
        });

        await test.step('merge the amendment into the motion', async () => {
            await expect(page.locator('.motionDataTable')).toContainText('Umwelt');
            await expect(page.locator('#sidebar .mergeamendments').filter({ visible: true })).toHaveCount(0);

            await logout(page);
            await loginAsConsultationAdmin(page);
            await page.locator('#sidebar .mergeamendments a').click();
            await expect(page.locator('.amendment279 .textProposal input')).toBeChecked();
            await expect(page.locator('.amendment280 .textProposal').filter({ visible: true })).toHaveCount(0);
            await page.locator('.amendment280 .colCheck input').first().uncheck();
            await page.locator('.mergeAllRow [type="submit"]').click();
            await page.waitForTimeout(1000);

            await expect(page.locator('#sections_2_1_wysiwyg ins')).toContainText('A really small replacement');
            await page.evaluate(() => {
                document.querySelectorAll('.none').forEach((el) => el.remove());
                document.querySelectorAll('#draftSavingPanel').forEach((el) => el.remove());
            });
            await page.locator('.motionMergeForm [name="save"]').click();
            await expect(page.locator('body')).toContainText('A really small replacement');
            await expect(page.locator('body')).not.toContainText('A big replacement', { useInnerText: true });
            await page.locator('#motionConfirmForm [name="confirm"]').click();
            await page.locator('#motionConfirmedForm [type="submit"]').click();
            await expect(page.locator('body')).toContainText('A really small replacement');

            await expect(page.locator('h1')).toContainText('Testing proposed changes');
            await expect(page.locator('.motionDataTable .historyOpener .currVersion')).toContainText('Version 2');
            await dispatchClick(page, '.motionDataTable .btnHistoryOpener');
            await expect(page.locator('.motionDataTable .motionHistory a')).toContainText('Version 1');
            await expect(page.locator('.motionDataTable')).toContainText('Umwelt');
            await page.goto('/stdparteitag/std-parteitag');
            await expect(
                page.locator(`.sectionResolutions .motionLink${FIRST_FREE_MOTION_ID + 1}`),
            ).toContainText('Testing proposed changes');
        });

    });
});
