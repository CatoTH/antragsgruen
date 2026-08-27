import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { dispatchClick } from '../../utils/dom';

test.describe('Merging: All amendments full workflow', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('merge all amendments, set statuses, save, and review history', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.gotoMotionView(2);
        await expect(page.locator('.sidebarActions .mergeamendments').filter({ visible: true })).toHaveCount(0);

        await loginAsStdAdmin(page);
        await page.locator('.sidebarActions .mergeamendments a').click();
        await page.waitForTimeout(500);
        await page.locator('.selectAll').first().click();

        await expect(page.locator('body')).toContainText('Einpflegen beginnen');
        await page.locator('.mergeAllRow .btn-primary').click();
        await page.waitForTimeout(500);
        await expect(page.locator('body')).toContainText('annehmen oder ablehnen');
        await expect(page.locator('.ice-ins')).toContainText('Neuer Punkt');
        await expect(page.locator('.ice-ins')).toContainText('Oamoi a Maß');
        await expect(page.locator('#sections_2_4_wysiwyg .ice-ins strong')).toContainText('Woibbadinga');

        await expect(page.locator('#sections_4_0_wysiwyg .ice-del')).toContainText('Woibbadinga damischa');
        await expect(page.locator('#sections_4_0_wysiwyg .ice-ins')).toContainText('Schooe');

        await expect(page.locator('#paragraphWrapper_2_4 .collisionsHolder')).toContainText('Kollidierender Änderungsantrag');
        await expect(page.locator('#paragraphWrapper_2_4 .collidingParagraph .ice-del')).toContainText('Woibbadinga noch da Giasinga');
        await expect(page.locator('#paragraphWrapper_2_4 .toggleAmendment3.toggleActive')).toHaveCount(1);
        await expect(page.locator('#paragraphWrapper_2_4 .toggleAmendment270.toggleActive')).toHaveCount(1);
        await expect(page.locator('#section_holder_2_4 .ice-del').getByText('Woibbadinga noch da Giasinga').filter({ visible: true })).toHaveCount(0);

        const cidCounts = await page.evaluate(() => {
            const get = (cid: string) => document.querySelectorAll(`[data-cid="${cid}"]`).length;
            return { cid0: get('0'), cid1: get('1'), cid2: get('2'), cid3: get('3') };
        });
        expect(cidCounts.cid0).toBe(9);
        expect(cidCounts.cid1).toBe(7);
        expect(cidCounts.cid2).toBe(3);
        expect(cidCounts.cid3).toBe(2);

        await page.evaluate(() => {
            const hint = document.querySelector(
                '#sections_2_1_wysiwyg [data-cid="2"] .appendHint',
            ) as HTMLElement | null;
            if (hint) {
                hint.dispatchEvent(new MouseEvent('mouseover', { bubbles: true }));
            }
            const btn = document.querySelector('button.reject') as HTMLElement | null;
            if (btn) btn.click();
        });
        await page.evaluate(() => {
            const hint = document.querySelector(
                '#sections_4_0_wysiwyg [data-cid="1"].appendHint',
            ) as HTMLElement | null;
            if (hint) {
                hint.dispatchEvent(new MouseEvent('mouseover', { bubbles: true }));
            }
            const btn = document.querySelector('button.accept') as HTMLElement | null;
            if (btn) btn.click();
        });
        await page.locator('#paragraphWrapper_2_4 .toggleAmendment270').click();

        await page.waitForTimeout(1000);

        await expect(page.locator('#sections_2_4_wysiwyg .ice-ins strong').getByText('Woibbadinga').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#sections_2_4_wysiwyg .ice-del')).toContainText('Woibbadinga noch da Giasinga');

        await expect(page.locator('#paragraphWrapper_2_4 .collisionsHolder').getByText('Kollidierender Änderungsantrag').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#paragraphWrapper_2_4 .collidingParagraph').getByText('Woibbadinga noch da Giasinga').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#paragraphWrapper_2_4 .toggleAmendment270.btn-default').first()).toBeVisible();
        await expect(page.locator('#paragraphWrapper_2_4 .toggleAmendment270.toggleActive').filter({ visible: true })).toHaveCount(0);

        await test.step('make some changes by hand', async () => {
            await expect(page.locator('#paragraphWrapper_2_7 .changedIndicator').filter({ visible: true })).toHaveCount(0);
            await page.evaluate(() => {
                const w = window as any;
                const data = w.CKEDITOR.instances.sections_2_7_wysiwyg.getData();
                w.CKEDITOR.instances.sections_2_7_wysiwyg.setData(data.replace(/Ende<\/ins>\./gi, 'Ende</ins>. With an hand-written appendix.'));
            });
            await page.waitForTimeout(500);
            await expect(page.locator('#paragraphWrapper_2_7 .changedIndicator')).toHaveCount(1);
            await expect(page.locator('#paragraphWrapper_2_7')).toContainText('With an hand-written appendix.');
        });

        await test.step('accept all changes in another paragraph', async () => {
            await expect(page.locator('#paragraphWrapper_4_2 .ice-del')).toContainText('Leonhardifahrt ma da middn');
            await page.locator('#paragraphWrapper_4_2 .acceptAll').first().click();
        });

        await expect(page.locator('#paragraphWrapper_2_4 .amendmentStatus3 .dropdown-menu').filter({ visible: true })).toHaveCount(0);
        await page.evaluate(() => {
            const btn = document.querySelector(
                '#paragraphWrapper_2_4 .amendmentStatus3 button.dropdown-toggle',
            ) as HTMLElement | null;
            if (btn) btn.click();
        });
        await expect(page.locator('#paragraphWrapper_2_4 .amendmentStatus3 .dropdown-menu').first()).toBeVisible();
        await page.locator('#votesComment2_4_3').first().fill('Accepted by a small margin');
        await page.locator('#votesYes2_4_3').first().fill('12');
        await page.locator('#votesNo2_4_3').first().fill('10');
        await page.locator('#votesInvalid2_4_3').first().fill('1');

        await expect(page.locator('#paragraphWrapper_2_7 .amendmentStatus3 .dropdown-menu').filter({ visible: true })).toHaveCount(0);
        await page.evaluate(() => {
            const btn = document.querySelector(
                '#paragraphWrapper_2_7 .amendmentStatus3 button.dropdown-toggle',
            ) as HTMLElement | null;
            if (btn) btn.click();
        });
        await expect(page.locator('#paragraphWrapper_2_7 .amendmentStatus3 .dropdown-menu').first()).toBeVisible();
        await expect(page.locator('#votesComment2_7_3')).toHaveValue('Accepted by a small margin');
        await expect(page.locator('#votesYes2_7_3')).toHaveValue('12');
        await expect(page.locator('#votesNo2_7_3')).toHaveValue('10');
        await expect(page.locator('#votesInvalid2_7_3')).toHaveValue('1');

        await page.evaluate(() => {
            const btn = document.querySelector(
                '#paragraphWrapper_2_7 .amendmentStatus274 button.dropdown-toggle',
            ) as HTMLElement | null;
            if (btn) btn.click();
        });
        await expect(page.locator('#paragraphWrapper_2_7 .amendmentStatus274 .dropdown-menu .status3.selected').first()).toBeVisible();
        await page.locator('#paragraphWrapper_2_7 .amendmentStatus274 .dropdown-menu .status5 a').click();

        await page.evaluate(() => {
            document.querySelectorAll('.none').forEach((el) => el.remove());
            document.querySelectorAll('#draftSavingPanel').forEach((el) => el.remove());
        });
        await page.waitForTimeout(1000);

        await test.step('Save the changes', async () => {
            await expect(page.locator('#paragraphWrapper_4_2').getByText('Leonhardifahrt ma da middn').filter({ visible: true })).toHaveCount(0);

            await page.locator('.motionMergeForm [name="save"]').click();

            await expect(page.locator('h1')).toContainText('Überarbeitung kontrollieren');
            await expect(page.locator('body')).toContainText('Oamoi a Maß');
            await expect(page.locator('body')).not.toContainText('Neuer Punkt', { useInnerText: true });
            await expect(page.locator('body')).toContainText('Alternatives Ende');
        });

        await test.step('change some amendment statuses', async () => {
            await expect(page.locator('#votesComment3')).toHaveValue('Accepted by a small margin');
            await expect(page.locator('#votesYes3')).toHaveValue('12');
            await expect(page.locator('#votesNo3')).toHaveValue('10');
            await expect(page.locator('#votesInvalid3')).toHaveValue('1');

            await page.locator('#votesComment3').first().fill('Accepted by a great margin');
            await page.locator('#votesYes3').first().fill('15');
            await page.locator('#votesNo3').first().fill('4');

            await expect(page.locator('#amendmentStatus274').locator('option:checked')).toHaveText(
                'Abgelehnt',
            );
            await page.locator('#amendmentStatus274').first().selectOption('4');
        });

        await test.step('modify the text', async () => {
            await page.locator('#motionConfirmForm [name="modify"]').click();

            await expect(page.locator('#paragraphWrapper_2_4 .collisionsHolder').getByText('Kollidierender Änderungsantrag').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#paragraphWrapper_2_4 .collidingParagraph').getByText('Woibbadinga noch da Giasinga').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#sections_2_4_wysiwyg .ice-del')).toContainText('Woibbadinga noch da Giasinga');
            await expect(page.locator('#paragraphWrapper_2_4 .toggleAmendment270.btn-default').first()).toBeVisible();
            await expect(page.locator('#paragraphWrapper_2_4 .toggleAmendment270.toggleActive').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#paragraphWrapper_2_7')).toContainText('With an hand-written appendix.');

            await expect(page.locator('#paragraphWrapper_2_4 .ice-ins')).toContainText('Oamoi a Maß und no a Maß');

            await expect(page.locator('#paragraphWrapper_2_7 .amendmentStatus3 .dropdown-menu').filter({ visible: true })).toHaveCount(0);
            await page.evaluate(() => {
                const btn = document.querySelector(
                    '#paragraphWrapper_2_7 .amendmentStatus3 button.dropdown-toggle',
                ) as HTMLElement | null;
                if (btn) btn.click();
            });
            await expect(page.locator('#paragraphWrapper_2_7 .amendmentStatus3 .dropdown-menu').first()).toBeVisible();
            await expect(page.locator('#votesComment2_7_3')).toHaveValue('Accepted by a great margin');
            await expect(page.locator('#votesYes2_7_3')).toHaveValue('15');
            await expect(page.locator('#votesNo2_7_3')).toHaveValue('4');
            await expect(page.locator('#votesInvalid2_7_3')).toHaveValue('1');

            await page.evaluate(() => {
                const btn = document.querySelector(
                    '#paragraphWrapper_2_7 .amendmentStatus274 button.dropdown-toggle',
                ) as HTMLElement | null;
                if (btn) btn.click();
            });
            await expect(page.locator('#paragraphWrapper_2_7 .amendmentStatus274 .dropdown-menu .status4.selected').first()).toBeVisible();

            await page.evaluate(() => {
                document.querySelectorAll('.none').forEach((el) => el.remove());
                document.querySelectorAll('#draftSavingPanel').forEach((el) => el.remove());
            });
            await page.waitForTimeout(1000);

            await page.locator('.motionMergeForm [name="save"]').click();
        });

        await test.step('add a voting result', async () => {
            await expect(page.locator('.contentVotingResult').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.contentVotingResultComment').filter({ visible: true })).toHaveCount(0);
            await page.locator('.votingResultOpener').click();
            await expect(page.locator('.contentVotingResult').first()).toBeVisible();
            await expect(page.locator('.contentVotingResultComment').first()).toBeVisible();
            await page.locator('#votesYes').first().fill('15');
            await page.locator('#votesNo').first().fill('5');
            await page.locator('#votesAbstention').first().fill('2');
            await page.locator('#votesInvalid').first().fill('0');
            await page.locator('#votesComment').first().fill('Accepted by mayority');

            await expect(page.locator('#votesComment3')).toHaveValue('Accepted by a great margin');
            await expect(page.locator('#votesYes3')).toHaveValue('15');
            await expect(page.locator('#votesNo3')).toHaveValue('4');
            await expect(page.locator('#votesInvalid3')).toHaveValue('1');
        });

        await test.step('submit the new form', async () => {
            await page.locator('#motionConfirmForm [name="confirm"]').click();

            await expect(page.locator('body')).toContainText('Der Antrag wurde überarbeitet');
            await page.locator('#motionConfirmedForm [type="submit"]').click();
        });

        await test.step('check if the modifications were made', async () => {
            await expect(page.locator('.statusRow')).toContainText('Beschluss');
            await expect(page.locator('.motionDataTable .historyOpener .currVersion')).toContainText('Version 2');
            await expect(page.locator('body')).toContainText('Oamoi a Maß');
            await expect(page.locator('body')).toContainText('Schooe');
            await expect(page.locator('body')).toContainText('With an hand-written appendix.');
            await expect(page.locator('body')).not.toContainText('Neuer Punkt', { useInnerText: true });
            await expect(page.locator('body')).toContainText('Alternatives Ende');
            await dispatchClick(page, '.motionDataTable .btnHistoryOpener');
            await expect(page.locator('.motionDataTable .motionHistory a.motion2')).toContainText('Version 1');
            await expect(page.locator('body')).not.toContainText('Leonhardifahrt ma da middn', { useInnerText: true });

            await expect(page.locator('.votingResultRow')).toContainText('Accepted by mayority');
            await expect(page.locator('.votingResultRow')).toContainText('Ja: 15, Nein: 5, Enthaltungen: 2');

            await dispatchClick(page, '.motionDataTable .btnHistoryOpener');
            await page.locator('.motionDataTable .motionHistory a.motion2').click();
            await expect(page.locator('.motionReplacedBy.alert-danger')).toContainText('Achtung: dies ist eine alte Fassung');
            await expect(page.locator('.bookmarks .amendment276').first()).toBeVisible();
            await expect(page.locator('.bookmarks .amendment3').first()).toBeVisible();

            await page.locator('.amendment3 a').click();
            await expect(page.locator('.votingResultRow')).toContainText('Accepted by a great margin');
            await expect(page.locator('.votingResultRow')).toContainText('Ja: 15, Nein: 4, Ungültig: 1');

            await home.gotoMotionView(122);
            await dispatchClick(page, '.motionDataTable .btnHistoryOpener');
            await page.locator('.motionDataTable .motionHistory a.motion2').click();
            await page.locator('.amendment274 a').click();
            await expect(page.locator('.statusRow')).toContainText('Angenommen');
        });

    });
});
