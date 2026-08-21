import { test, expect } from '../../fixtures';
import { MotionPage } from '../../pages/MotionPage';
import { AmendmentPage } from '../../pages/AmendmentPage';
import {ConsultationHomePage} from '../../pages/BasePage';
import { loginAsStdAdmin } from '../../utils/auth';
import {
    FIRST_FREE_AGENDA_ITEM_ID,
    FIRST_FREE_AMENDMENT_ID,
    FIRST_FREE_MOTION_ID,
} from '../../utils/constants';
import { dispatchClick } from '../../utils/dom';

test.describe('Admin: MoveMotionsWithinConsultation', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('prepare comments, agenda layout and items', async ({ page }) => {
        await loginAsStdAdmin(page);
        await new MotionPage(page).open({ motionSlug: 'Testing_proposed_changes-630' });
        await page.locator('#comment_-1_-1_text').fill('A motion comment');
        await page.locator('#comment_-1_-1_form [name="writeComment"]').click();
        await expect(page.locator('body')).toContainText('A motion comment');

        await new AmendmentPage(page).open({
            motionSlug: 'Testing_proposed_changes-630',
            amendmentId: 279,
        });
        await page.locator('#comment_-1_-1_text').fill('An amendment comment');
        await page.locator('#comment_-1_-1_form [name="writeComment"]').click();
        await expect(page.locator('body')).toContainText('An amendment comment');

        await page.locator('#adminLink').click();
        await page.locator('#appearanceLink').click();
        await page.locator('#startLayoutType').selectOption('3');
        await page.locator('#appearanceForm [name="save"]').click();

        await new ConsultationHomePage(page).open();

        await page.locator('.agendaEditLink').click();
        await expect(page.locator('.agendaEditWidget')).toBeVisible();

        await page.evaluate(() => {
            const w = window as any;
            const agendaWidget = w.agendaWidget;
            agendaWidget.$refs['agenda-edit-widget'].setAgendaTest({
                items: [
                    {
                        type: 'item',
                        code: null,
                        title: 'Earth',
                        settings: {
                            has_speaking_list: false,
                            in_proposed_procedures: true,
                            motion_types: [],
                        },
                        children: [],
                    },
                    {
                        type: 'item',
                        code: null,
                        title: 'Mars',
                        settings: {
                            has_speaking_list: false,
                            in_proposed_procedures: true,
                            motion_types: [],
                        },
                        children: [],
                    },
                    {
                        type: 'item',
                        code: null,
                        title: 'venus',
                        settings: {
                            has_speaking_list: false,
                            in_proposed_procedures: true,
                            motion_types: [],
                        },
                        children: [],
                    },
                ],
            });
        });
        await dispatchClick(page, '.agendaEditWidget .btnSave');
    });

    test('move a motion to an agenda item', async ({ page }) => {
        await loginAsStdAdmin(page);
        const earth = FIRST_FREE_AGENDA_ITEM_ID;
        const mars = FIRST_FREE_AGENDA_ITEM_ID + 1;
        const venus = FIRST_FREE_AGENDA_ITEM_ID + 2;

        await page.locator('#motionListLink').click();
        await page
            .locator('.motion118 .edit, .motion118 [href*="edit"]')
            .first()
            .click();
        await page.locator('#agendaItemId').selectOption(String(earth));
        await page.locator('#motionUpdateForm [name="save"]').click();

        await new ConsultationHomePage(page).open();
        await expect(page.locator(`.agenda${earth} .motionRow118`)).toBeVisible();
    });

    test('finally move it, without reference', async ({ page }) => {
        await loginAsStdAdmin(page);
        const mars = FIRST_FREE_AGENDA_ITEM_ID + 1;

        await page.locator('#motionListLink').click();
        await page
            .locator('.motion118 .edit, .motion118 [href*="edit"]')
            .first()
            .click();
        await page.locator('.sidebarActions .move').click();

        await expect(page.locator('.moveToAgendaItem')).not.toBeVisible();
        await page.locator("input[name='operation'][value='move']").check();
        await expect(page.locator("input[name='operation'][value='move']")).toBeChecked();
        await page.locator("input[name='target'][value='agenda']").check();
        await expect(page.locator('.moveToAgendaItem')).toBeVisible();
        await page.locator('#agendaItemId1').selectOption(String(mars));
        await page.locator('.adminMoveForm [name="move"]').click();

        await expect(page.locator('h1')).toContainText('A8: Testing proposed changes');
        await expect(page.locator('.motionDataTable')).toContainText('2. Mars');

        await new ConsultationHomePage(page).open();
        await expect(page.locator(`.agenda${FIRST_FREE_AGENDA_ITEM_ID}`)).not.toBeVisible();
        await expect(
            page.locator(`.agenda${mars} .motionRow118`),
        ).toBeVisible();
    });

    test('finally move it, with reference', async ({ page }) => {
        await loginAsStdAdmin(page);
        const venus = FIRST_FREE_AGENDA_ITEM_ID + 2;

        await page.locator('#motionListLink').click();
        await page
            .locator('.motion118 .edit, .motion118 [href*="edit"]')
            .first()
            .click();
        await page.locator('.sidebarActions .move').click();

        await expect(page.locator('.moveToAgendaItem')).not.toBeVisible();
        await page.locator('#motionTitlePrefix').fill('A8M');
        await page.locator("input[name='operation'][value='copy']").check();
        await page.locator("input[name='target'][value='agenda']").check();
        await expect(page.locator('.moveToAgendaItem')).toBeVisible();
        await page.locator('#agendaItemId1').selectOption(String(venus));
        await page.locator('.adminMoveForm [name="move"]').click();

        await expect(page.locator('h1')).toContainText('A8M: Testing proposed changes');
        await expect(page.locator('.motionDataTable')).toContainText('3. Venus');
        await expect(page.locator('body')).toContainText('A motion comment');

        await new ConsultationHomePage(page).open();
        await expect(page.locator(`.agenda${FIRST_FREE_AGENDA_ITEM_ID}`)).not.toBeVisible();
        await expect(
            page.locator(`.agenda${FIRST_FREE_AGENDA_ITEM_ID + 1} .motionRow118.moved`),
        ).toBeVisible();
        await expect(
            page.locator(`.agenda${venus} .motionRow${FIRST_FREE_MOTION_ID}`),
        ).toBeVisible();
        await expect(page.locator(`.motionRow${FIRST_FREE_MOTION_ID}`)).toContainText('A8M');
        await page
            .locator(
                `.motionRow${FIRST_FREE_MOTION_ID} .amendmentRow${FIRST_FREE_AMENDMENT_ID} .amendmentTitle`,
            )
            .click();

        await expect(page.locator('ins')).toContainText('A small replacement');
        await expect(page.locator('body')).toContainText('Von Zeile 7 bis 9:');
        await expect(page.locator('body')).toContainText('An amendment comment');
    });

    test('exported proposed procedure reflects moved motions', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#motionListLink').click();
        await page.locator('#exportProcedureBtn').click();
        await page.locator('.linkProcedureIntern').click();

        await expect(page.locator('.motion118.moved')).toBeVisible();
        await expect(page.locator('.motion118')).toContainText('Verschoben');
        await page.locator('.motion118 .moved a').click();
        await expect(page.locator('h1')).toContainText('A8M: Testing proposed changes');
    });

    test('merge amendments into the copied motion', async ({ page }) => {
        await loginAsStdAdmin(page);
        await new MotionPage(page).open({ motionSlug: FIRST_FREE_MOTION_ID });
        await page.locator('#sidebar .mergeamendments a').click();
        await dispatchClick(page, '.toMergeAmendments .selectAll');
        await page.locator('.mergeAllRow [type="submit"]').click();
        await expect(
            page.locator('#paragraphWrapper_2_1 .collidingParagraph'),
        ).toContainText('A big replacement');
    });

    test('copy the new motion within the consultation', async ({ page }) => {
        await loginAsStdAdmin(page);
        await new MotionPage(page).open({ motionSlug: FIRST_FREE_MOTION_ID });
        await page.locator('#sidebar .adminEdit a').click();
        await page.locator('.sidebarActions .move').click();

        await expect(page.locator('.moveToConsultationItem')).not.toBeVisible();
        await expect(page.locator('.targetSame')).not.toBeVisible();
        await page.locator("input[name='operation'][value='copynoref']").check();
        await expect(page.locator("input[name='operation'][value='copynoref']")).toBeChecked();
        await expect(page.locator('.targetSame')).toBeVisible();
        await page.locator("input[name='target'][value='same']").check();
        await expect(page.locator('.moveToConsultationItem')).not.toBeVisible();
        await expect(page.locator('.prefixAlreadyTaken')).toBeVisible();

        await page.locator('#motionTitlePrefix').fill('N1.2');
        await expect(page.locator('.prefixAlreadyTaken')).not.toBeVisible();

        await page.locator('.adminMoveForm [name="move"]').click();
        await expect(page.locator('h1')).toContainText('N1.2');
        await expect(page.locator('.motionDataTable .motionHistory')).not.toBeVisible();
    });
});