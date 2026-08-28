import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin } from '../../utils/auth';
import {
    FIRST_FREE_MOTION_ID,
    FIRST_FREE_AMENDMENT_ID,
} from '../../utils/constants';
import { replaceInCkEditor, setCkEditorContent, expectBootboxDialog, acceptBootbox } from '../../utils/dom';

test.describe('Admin: FixedText', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('switch on screening and create two motions and amendments', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).open();
        await page.locator('#adminLink').click();
        await page.locator('.motionType1').click();
        await page.locator('#screeningMotions').check();
        await page.locator('#screeningAmendments').check();
        await page.locator('.adminTypeForm [name="save"]').first().click();

        await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');
        await replaceInCkEditor(page, 'sections_2_wysiwyg', /woschechta Bayer/g, 'Saupreiß');
        await setCkEditorContent(page, 'amendmentReason_wysiwyg', '<p>This is my reason</p>');
        await page.locator('#initiatorPrimaryName').fill('Mein Name');
        await page.locator('#initiatorEmail').fill('test@example.org');
        await page.locator('#sections_1').fill('Neuer Testantrag 1');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();

        await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');
        await replaceInCkEditor(page, 'sections_2_wysiwyg', /woschechta Bayer/g, 'Saupreiß');
        await setCkEditorContent(page, 'amendmentReason_wysiwyg', '<p>This is my reason</p>');
        await page.locator('#initiatorPrimaryName').fill('Mein Name');
        await page.locator('#initiatorEmail').fill('test@example.org');
        await page.locator('#sections_1').fill('Neuer Testantrag 2');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();

        const motionCreate = await new ConsultationHomePage(page).gotoMotionCreatePage();
        await motionCreate.fillInValidSampleData('Testantrag 1');
        await page.locator('#motionEditForm [name="save"]').click();
        await page.locator('#motionConfirmForm [name="confirm"]').click();

        const motionCreate2 = await new ConsultationHomePage(page).gotoMotionCreatePage();
        await motionCreate2.fillInValidSampleData('Testantrag 2');
        await page.locator('#motionEditForm [name="save"]').click();
        await page.locator('#motionConfirmForm [name="confirm"]').click();
    });

    test('screen two of them; editing is still possible', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).open();
        await page.locator('#motionListLink').click();
        await page
            .locator(`.amendment${FIRST_FREE_AMENDMENT_ID} .edit, .amendment${FIRST_FREE_AMENDMENT_ID} [href*="edit"]`)
            .first()
            .click();
        await expect(page.locator('#amendmentTextEditCaller')).toBeVisible();
        await page.locator('#amendmentScreenForm [name="screen"]').click();
        await expect(page.locator('#amendmentTextEditCaller')).toBeVisible();

        await page.locator('#motionListLink').click();
        await page
            .locator(`.motion${FIRST_FREE_MOTION_ID} .edit, .motion${FIRST_FREE_MOTION_ID} [href*="edit"]`)
            .first()
            .click();
        await expect(page.locator('#motionTextEditCaller')).toBeVisible();
        await page.locator('#motionScreenForm [name="screen"]').click();
        await expect(page.locator('#motionTextEditCaller')).toBeVisible();
    });

    test('disable editing published text', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('#consultationLink').click();

        await expect(page.locator('#iniatorsMayEdit')).toBeVisible();
        await page.locator('#adminsMayEdit').uncheck();
        await expectBootboxDialog(page, /wirkt sich das auch auf alle bisherigen Anträge aus/);
        await acceptBootbox(page);
        await expect(page.locator('#iniatorsMayEdit')).not.toBeVisible();
        await page.locator('#consultationSettingsForm [name="save"]').click();
    });

    test('check that published motions are not editable anymore', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).open();
        await page.locator('#motionListLink').click();
        await page
            .locator(`.amendment${FIRST_FREE_AMENDMENT_ID} .edit, .amendment${FIRST_FREE_AMENDMENT_ID} [href*="edit"]`)
            .first()
            .click();
        await expect(page.locator('#amendmentTextEditCaller')).not.toBeVisible();

        await page.locator('#motionListLink').click();
        await page
            .locator(`.amendment${FIRST_FREE_AMENDMENT_ID + 1} .edit, .amendment${FIRST_FREE_AMENDMENT_ID + 1} [href*="edit"]`)
            .first()
            .click();
        await expect(page.locator('#amendmentTextEditCaller')).toBeVisible();
        await page.locator('#amendmentScreenForm [name="screen"]').click();
        await expect(page.locator('#amendmentTextEditCaller')).not.toBeVisible();

        await page.locator('#motionListLink').click();
        await page
            .locator(`.motion${FIRST_FREE_MOTION_ID} .edit, .motion${FIRST_FREE_MOTION_ID} [href*="edit"]`)
            .first()
            .click();
        await expect(page.locator('#motionTextEditCaller')).not.toBeVisible();

        await page.locator('#motionListLink').click();
        await page
            .locator(`.motion${FIRST_FREE_MOTION_ID + 1} .edit, .motion${FIRST_FREE_MOTION_ID + 1} [href*="edit"]`)
            .first()
            .click();
        await expect(page.locator('#motionTextEditCaller')).toBeVisible();
        await page.locator('#motionScreenForm [name="screen"]').click();
        await expect(page.locator('#motionTextEditCaller')).not.toBeVisible();
    });
});