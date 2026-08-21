import { test, expect } from '../../fixtures';
import { MotionPage } from '../../pages/MotionPage';
import {ConsultationHomePage} from '../../pages/BasePage';
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
        await loginAsStdAdmin(page);
        await expect(page.locator('#adminTodo')).not.toBeVisible();
        await page.locator('#adminLink').click();
        await page.locator('.motionType1').click();
        await expect(page.locator('#screeningAmendments')).not.toBeChecked();
        await page.locator('#screeningAmendments').check();
        await page.locator('.adminTypeForm [name="save"]').click();
        await expect(page.locator('#screeningAmendments')).toBeChecked();
    });

    test('create an amendment as a logged out user', async ({ page }) => {
        await logout(page);
        await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');
        await replaceInCkEditor(page, 'sections_2_wysiwyg', /woschechta Bayer/g, 'Saupreiß');
        await setCkEditorContent(page, 'amendmentReason_wysiwyg', '<p>This is my reason</p>');
        await page.locator('#sections_1').fill('Neuer Testantrag');
        await page.locator('#initiatorPrimaryName').fill('Mein Name');
        await page.locator('#initiatorEmail').fill('test@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();
        await expect(page.locator('body')).toContainText(
            'Er wird nun auf formale Richtigkeit geprüft und dann freigeschaltet.',
        );
    });

    test('check that the amendment is not visible yet', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await expect(page.locator(`.amendment${FIRST_FREE_AMENDMENT_ID}`)).not.toBeVisible();
        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator(`.amendment${FIRST_FREE_AMENDMENT_ID}`)).not.toBeVisible();
    });

    test('screen the amendment with an invalid title prefix (race condition)', async ({
        page,
    }) => {
        await loginAsStdAdmin(page);
        await page.locator('#adminTodo').click();
        await expect(
            page.locator(`.adminTodo .amendmentsScreen${FIRST_FREE_AMENDMENT_ID}`),
        ).toBeVisible();
        await page.locator(`.adminTodo .amendmentsScreen${FIRST_FREE_AMENDMENT_ID} a`).click();

        await expect(page.locator('#amendmentScreenForm')).toBeVisible();
        await page.evaluate(() => {
            const w = window as any;
            w.$('#amendmentScreenForm input[name=titlePrefix]').attr('value', 'Ä2');
        });
        await page.locator('#amendmentScreenForm [name="screen"]').click();
        await expect(page.locator('body')).toContainText(
            'Das angegebene Antragskürzel wird bereits von einem anderen Änderungsantrag verwendet.',
        );
    });

    test('screen the amendment normally', async ({ page }) => {
        await loginAsStdAdmin(page);
        await expect(page.locator('#amendmentScreenForm')).toBeVisible();
        await page.locator('#amendmentScreenForm [name="screen"]').click();
        await expect(page.locator('body')).toContainText(
            'Der Änderungsantrag wurde freigeschaltet.',
        );
    });

    test('check if the amendment is visible now', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await expect(
            page.locator(`.motionListStd .amendment${FIRST_FREE_AMENDMENT_ID}`),
        ).toBeVisible();
        await expect(
            page.locator(`#sidebar ul.amendments .amendment${FIRST_FREE_AMENDMENT_ID}`),
        ).toBeVisible();
    });
});