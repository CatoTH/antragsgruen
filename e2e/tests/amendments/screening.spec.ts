import { test, expect } from '../../fixtures';
import { MotionPage } from '../../pages/MotionPage';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import {
    FIRST_FREE_AMENDMENT_ID,
} from '../../utils/constants';
import { replaceInCkEditor, setCkEditorContent } from '../../utils/dom';

test.describe('Amendments: Screening', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('switch to amendment screening mode', async ({ page }) => {
        await test.step('switch to amendment screening mode', async () => {
            await new ConsultationHomePage(page).open();
            await loginAsStdAdmin(page);
            await test.step('go to the admin page', async () => {
                await expect(page.locator('#adminTodo').filter({ visible: true })).toHaveCount(0);
                await page.locator('#adminLink').click();
                await page.locator('.motionType1').click();
                await expect(page.locator('#screeningAmendments')).not.toBeChecked();
                await page.locator('#screeningAmendments').first().check();
                await page.locator('.adminTypeForm [name="save"]').first().click();
                await expect(page.locator('#screeningAmendments')).toBeChecked();
            });
        });

        await test.step('create an amendment as a logged out user', async () => {
            await logout(page);
            await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');
            await replaceInCkEditor(page, 'sections_2_wysiwyg', /woschechta Bayer/g, 'Saupreiß');
            await setCkEditorContent(page, 'amendmentReason_wysiwyg', '<p>This is my reason</p>');
            await page.locator('#sections_1').first().fill('Neuer Testantrag');
            await page.locator('#initiatorPrimaryName').first().fill('Mein Name');
            await page.locator('#initiatorEmail').first().fill('test@example.org');
            await page.locator('#amendmentEditForm [name="save"]').click();
            await page.locator('#amendmentConfirmForm [name="confirm"]').click();
            await expect(page.locator('body')).toContainText(
                'Er wird nun auf formale Richtigkeit geprüft und dann freigeschaltet.',
            );
        });

        await test.step('check that the amendment is not visible yet', async () => {
            await expect(page.locator(`.amendment${FIRST_FREE_AMENDMENT_ID}`).filter({ visible: true })).toHaveCount(0);
            await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
            await expect(page.locator(`.amendment${FIRST_FREE_AMENDMENT_ID}`).filter({ visible: true })).toHaveCount(0);
        });

        await test.step('screen the amendment with an invalid title prefix (race condition)', async () => {
            await page.locator('#adminTodo').click();
            await expect(
                page.locator(`.adminTodo .amendmentsScreen${FIRST_FREE_AMENDMENT_ID}`),
            ).toBeVisible();
            await page.locator(`.adminTodo .amendmentsScreen${FIRST_FREE_AMENDMENT_ID} a`).click();

            await expect(page.locator('#amendmentScreenForm').first()).toBeVisible();
            await page.evaluate(() => {
                const w = window as any;
                w.$('#amendmentScreenForm input[name=titlePrefix]').attr('value', 'Ä2');
            });
            await page.locator('#amendmentScreenForm [name="screen"]').click();
            await expect(page.locator('body')).toContainText(
                'Das angegebene Antragskürzel wird bereits von einem anderen Änderungsantrag verwendet.',
            );
        });

        await test.step('screen the amendment normally', async () => {
            await expect(page.locator('#amendmentScreenForm').first()).toBeVisible();
            await page.locator('#amendmentScreenForm [name="screen"]').click();
            await expect(page.locator('body')).toContainText(
                'Der Änderungsantrag wurde freigeschaltet.',
            );
        });

        await test.step('check if the amendment is visible now', async () => {
            await expect(
                page.locator(`.motionListStd .amendment${FIRST_FREE_AMENDMENT_ID}`),
            ).toBeVisible();
            await expect(
                page.locator(`#sidebar ul.amendments .amendment${FIRST_FREE_AMENDMENT_ID}`),
            ).toBeVisible();
        });
    });
});