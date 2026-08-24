import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import {
    FIRST_FREE_AGENDA_ITEM_ID,
    FIRST_FREE_AMENDMENT_ID,
    FIRST_FREE_MOTION_ID,
    FIRST_FREE_MOTION_SECTION,
    FIRST_FREE_MOTION_TYPE,
} from '../../utils/constants';
import { dispatchClick, replaceInCkEditor, setCkEditorContent } from '../../utils/dom';

test.describe('Amendments: StatuteAmendments', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('activate statute amendments and create a base statute', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();

        await page.locator('.motionTypeCreate a').click();
        await page.locator('.presetStatute').check();
        await page.locator('#typeMotionPrefix').fill('S');
        await page.locator('.motionTypeCreateForm [name="create"]').click();

        await expect(page.locator('body')).toContainText('Satzungsänderungsanträge: Basistexte');
        await expect(page.locator('.baseStatutesNone')).toBeVisible();

        await expect(page.locator('#typePolicySupportMotions')).not.toBeVisible();
        await expect(page.locator('#typePolicySupportAmendments')).toBeVisible();
        await expect(page.locator('#motionSupportersForm')).not.toBeVisible();
        await expect(page.locator('#amendmentSupportersForm')).toBeVisible();

        const sectionId = `sections_${FIRST_FREE_MOTION_SECTION + 1}_wysiwyg`;
        await page.locator('.statuteCreateLnk').click();
        await page
            .locator(`#sections_${FIRST_FREE_MOTION_SECTION}`)
            .fill('Our statutes');
        await setCkEditorContent(
            page,
            sectionId,
            '<h2>Section 1</h2><ol><li>Article 1</li><li>Article 2</li></ol>',
        );
        await page.locator('#motionEditForm [name="save"]').click();
        await page.locator('#motionConfirmForm [name="confirm"]').click();
        await page.locator('.btnBack').click();

        await expect(
            page.locator(`.baseStatutesList .statute${FIRST_FREE_MOTION_ID}`),
        ).toContainText('Our statutes');
    });

    test('create an amendment', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdUser(page);
        await new ConsultationHomePage(page).open();

        await expect(page.locator('body')).not.toContainText('Our statutes');

        await page.locator(`#sidebar .createMotion${FIRST_FREE_MOTION_TYPE} a`).click();

        const sectionId = `sections_${FIRST_FREE_MOTION_SECTION + 1}_wysiwyg`;
        await replaceInCkEditor(page, sectionId, /Article/g, 'Paragraph');
        await setCkEditorContent(page, 'amendmentReason_wysiwyg', '<p>This is my reason</p>');

        await page.locator("input[name='Initiator[primaryName]']").fill('My Name');
        await page.locator("input[name='Initiator[contactEmail]']").fill('test@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();
        await page.locator('#motionConfirmedForm .btn').click();

        await logout(page);
        await new ConsultationHomePage(page).open();
        await expect(
            page.locator(`.amendmentRow${FIRST_FREE_AMENDMENT_ID}`),
        ).toContainText('S1');
        await expect(
            page.locator(`.amendmentRow${FIRST_FREE_AMENDMENT_ID}`),
        ).toContainText('Our Statutes');
        await page.locator(`.amendmentRow${FIRST_FREE_AMENDMENT_ID} a`).click();
        await expect(page.locator('#sidebar .amendmentCreate')).not.toBeVisible();
    });

    test('Create amendments to statute amendments', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator(`.motionType${FIRST_FREE_MOTION_TYPE}`).click();
        await page.locator('#allowAmendmentsToAmendments').check();
        await page.locator('.adminTypeForm [name="save"]').first().click();
        await logout(page);
        await loginAsStdUser(page);
        await page.locator(`.amendmentRow${FIRST_FREE_AMENDMENT_ID} a`).click();
        await page.locator('#sidebar .amendmentCreate').click();

        const sectionId = `sections_${FIRST_FREE_MOTION_SECTION + 1}_wysiwyg`;
        await replaceInCkEditor(page, sectionId, /Paragraph/g, 'Section');
        await page.locator("input[name='Initiator[primaryName]']").fill('My Name');
        await page.locator("input[name='Initiator[contactEmail]']").fill('test@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();
        await page.locator('#motionConfirmedForm .btn').click();

        await new ConsultationHomePage(page).open();
        await page.locator(`.amendmentRow${FIRST_FREE_AMENDMENT_ID + 1} a`).click();
        await expect(
            page.locator(
                `#original_section_${FIRST_FREE_MOTION_SECTION + 1} .inserted`,
            ),
        ).toContainText('Paragraph');
        await expect(
            page.locator(
                `#original_section_${FIRST_FREE_MOTION_SECTION + 1} .deleted`,
            ),
        ).toContainText('Article');
        await expect(
            page.locator(`#section_${FIRST_FREE_MOTION_SECTION + 1} .inserted`),
        ).toContainText('Section');
        await expect(
            page.locator(`#section_${FIRST_FREE_MOTION_SECTION + 1} .deleted`),
        ).toContainText('Article');
    });

    test('set up an agenda and assign the statute amendment to it', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
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

    test('check the home pages (with no agenda item assigned)', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        const earth = FIRST_FREE_AGENDA_ITEM_ID;
        await new ConsultationHomePage(page).open();
        await expect(page.locator('.consultationIndex')).toBeVisible();
        await expect(
            page.locator(`.amendmentLink${FIRST_FREE_AMENDMENT_ID}`),
        ).toContainText('Our statutes');
        await expect(
            page.locator(`.amendmentRow${FIRST_FREE_AMENDMENT_ID}`),
        ).toContainText('S1');
        await expect(
            page.locator(
                `.amendmentRow${FIRST_FREE_AMENDMENT_ID} .amendmentRow${FIRST_FREE_AMENDMENT_ID + 1}`,
            ),
        ).toContainText('Ä1');
    });

    test('check the home pages (with an agenda item assigned)', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        const earth = FIRST_FREE_AGENDA_ITEM_ID;
        await page.locator('#motionListLink').click();
        await page
            .locator(`.amendment${FIRST_FREE_AMENDMENT_ID} .edit, .amendment${FIRST_FREE_AMENDMENT_ID} [href*="edit"]`)
            .first()
            .click();
        await page.locator('#agendaItemId').selectOption(String(earth));
        await page.locator('#amendmentUpdateForm [name="save"]').click();

        await page.locator('#adminLink').click();
        await page.locator('#appearanceLink').click();
        await page.locator('#startLayoutType').selectOption('3');
        await page.locator('#appearanceForm [name="save"]').click();
        await new ConsultationHomePage(page).open();
        await expect(
            page.locator(
                `#agendaitem_${earth} .amendmentLink${FIRST_FREE_AMENDMENT_ID}`,
            ),
        ).toContainText('Our statutes');
        await expect(
            page.locator(
                `#agendaitem_${earth} .amendmentLink${FIRST_FREE_AMENDMENT_ID}`,
            ),
        ).toContainText('S1');

        await page.locator('#adminLink').click();
        await page.locator('#appearanceLink').click();
        await page.locator('#startLayoutType').selectOption('4');
        await page.locator('#appearanceForm [name="save"]').click();
        await new ConsultationHomePage(page).open();
        await expect(
            page.locator(
                `.agenda${earth} .amendmentLink${FIRST_FREE_AMENDMENT_ID}`,
            ),
        ).toContainText('Our statutes');
        await expect(
            page.locator(
                `.agenda${earth} .amendmentLink${FIRST_FREE_AMENDMENT_ID}`,
            ),
        ).toContainText('S1');
    });

    test('check the amendment view', async ({ page }) => {
        await page.locator(`.amendmentLink${FIRST_FREE_AMENDMENT_ID}`).click();
        await expect(page.locator('.motionRow')).toHaveCount(0);
        await expect(page.locator('body')).toContainText('This is my reason');
        await expect(page.locator('.deleted')).toContainText('Article 1');
        await expect(page.locator('.inserted')).toContainText('Paragraph 1');
        await expect(page.locator('#sidebar .back')).toContainText('Zurück zur Übersicht');
        await page.locator('#sidebar .back a').click();
        await expect(page.locator('.consultationIndex')).toBeVisible();
    });

    test('check what happens if there are two statutes and amendments', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator(`.motionType${FIRST_FREE_MOTION_TYPE}`).click();
        await expect(page.locator('.baseStatutesList')).toBeVisible();

        await page.locator('.statuteCreateLnk').click();
        await page
            .locator(`#sections_${FIRST_FREE_MOTION_SECTION}`)
            .fill('Our second statutes');
        const sectionId = `sections_${FIRST_FREE_MOTION_SECTION + 1}_wysiwyg`;
        await setCkEditorContent(page, sectionId, '<h2>Another part of the statutes</h2>');
        await page.locator('#motionEditForm [name="save"]').click();
        await page.locator('#motionConfirmForm [name="confirm"]').click();
        await page.locator('.btnBack').click();

        await new ConsultationHomePage(page).open();
        await page.locator(`#sidebar .createMotion${FIRST_FREE_MOTION_TYPE} a`).click();
        await expect(page.locator('.createSelectStatutes')).toBeVisible();
        await page.locator(`.statute${FIRST_FREE_MOTION_ID + 1} a`).click();

        await setCkEditorContent(page, sectionId, '<p>A completely different text</p>');
        await setCkEditorContent(page, 'amendmentReason_wysiwyg', '<p>This is my reason</p>');

        await page.locator("input[name='Initiator[primaryName]']").fill('My Name');
        await page.locator("input[name='Initiator[contactEmail]']").fill('test@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();
        await page.locator('#motionConfirmedForm .btn').click();

        await expect(
            page.locator(`.amendmentLink${FIRST_FREE_AMENDMENT_ID + 2}`),
        ).toContainText('Our second statutes');
        await expect(
            page.locator(`.amendmentLink${FIRST_FREE_AMENDMENT_ID + 2}`),
        ).toContainText('S2');
    });
});