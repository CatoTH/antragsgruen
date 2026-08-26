import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin } from '../../utils/auth';
import {
    FIRST_FREE_AGENDA_ITEM_ID,
    FIRST_FREE_AMENDMENT_ID,
    FIRST_FREE_MOTION_ID,
    FIRST_FREE_MOTION_SECTION,
} from '../../utils/constants';
import { dispatchClick, setCkEditorContent } from '../../utils/dom';

test.describe('Amendments: StatuteAmendmentsAgenda', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('activate statute amendments and create two base statutes', async ({ page }) => {
        await test.step('activate statute amendments and create two base statutes', async () => {
            await new ConsultationHomePage(page).open();
            await loginAsStdAdmin(page);
            await page.locator('#adminLink').click();

            await page.locator('.motionTypeCreate a').click();
            await page.locator('.presetStatute').first().check();
            await page.locator('#typeMotionPrefix').first().fill('ST');
            await page.locator('#typeTitleSingular').first().fill('Statute amendment');
            await page.locator('#typeTitlePlural').first().fill('Statute amendments');
            await page.locator('#typeCreateTitle').first().fill('Create a statute amendment');
            await page.locator('.motionTypeCreateForm [name="create"]').click();

            await expect(page.locator('body')).toContainText('Satzungsänderungsanträge: Basistexte');
            await expect(page.locator('.baseStatutesNone').first()).toBeVisible();

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

            await page.locator('.statuteCreateLnk').click();
            await page
                .locator(`#sections_${FIRST_FREE_MOTION_SECTION}`)
                .fill('Additional statute');
            await setCkEditorContent(page, sectionId, '<p>Dummy text</p>');
            await page.locator('#motionEditForm [name="save"]').click();
            await page.locator('#motionConfirmForm [name="confirm"]').click();
            await page.locator('.btnBack').click();

            await expect(
                page.locator(`.baseStatutesList .statute${FIRST_FREE_MOTION_ID}`),
            ).toContainText('Our statutes');
            await expect(
                page.locator(`.baseStatutesList .statute${FIRST_FREE_MOTION_ID + 1}`),
            ).toContainText('Additional statute');
        });

        await test.step('create an agenda', async () => {
            await page.locator('#adminLink').click();
            await page.locator('#appearanceLink').click();
            await page.locator('#startLayoutType').first().selectOption('2');
            await page.locator('#appearanceForm [name="save"]').click();

            await new ConsultationHomePage(page).open();
            await page.locator('.agendaEditLink').click();
            await expect(page.locator('.agendaEditWidget').first()).toBeVisible();

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
                                motion_types: [
                                    FIRST_FREE_MOTION_SECTION + 16,
                                ],
                            },
                            children: [],
                        },
                    ],
                });
            });
            await dispatchClick(page, '.agendaEditWidget .btnSave');
        });

        await test.step('test that creating the statute amendment works', async () => {
            await expect(
                page.locator(
                    `#agendaitem_${FIRST_FREE_AGENDA_ITEM_ID + 1} .motionCreateLink`,
                ),
            ).toContainText('Create a statute amendment');
            await page
                .locator(`#agendaitem_${FIRST_FREE_AGENDA_ITEM_ID + 1} .motionCreateLink`)
                .click();
            await expect(page.locator('h1')).toContainText('Mars: Statute amendment');

            await page.locator(`.statute${FIRST_FREE_MOTION_ID + 1} a`).click();
            const sectionId = `sections_${FIRST_FREE_MOTION_SECTION + 1}_wysiwyg`;
            await setCkEditorContent(page, sectionId, '<p>Set a new text</p>');
            await setCkEditorContent(page, 'amendmentReason_wysiwyg', '<p>Reason</p>');
            await page.locator("input[name='Initiator[primaryName]']").first().fill('My Name');
            await page.locator("input[name='Initiator[contactEmail]']").first().fill('test@example.org');
            await page.locator('#amendmentEditForm [name="save"]').click();

            await expect(page.locator('ins')).toContainText('Set a new');
            await page.locator('#amendmentConfirmForm [name="confirm"]').click();
            await page.locator('#motionConfirmedForm [type="submit"]').click();
        });

        await test.step('see the new amendment being assigned to the agenda item', async () => {
            await expect(
                page.locator(
                    `#agendaitem_${FIRST_FREE_AGENDA_ITEM_ID + 1} .amendmentRow${FIRST_FREE_AMENDMENT_ID}`,
                ),
            ).toContainText('ST1');
            await expect(
                page.locator(
                    `#agendaitem_${FIRST_FREE_AGENDA_ITEM_ID + 1} .amendmentRow${FIRST_FREE_AMENDMENT_ID}`,
                ),
            ).toContainText('Additional statute');
            await page
                .locator(
                    `#agendaitem_${FIRST_FREE_AGENDA_ITEM_ID + 1} .amendmentRow${FIRST_FREE_AMENDMENT_ID} .title a`,
                )
                .click();
            await expect(page.locator('ins')).toContainText('Set a new');
        });
    });
});