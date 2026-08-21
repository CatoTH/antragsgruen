import { test, expect } from '../../fixtures';

import { AmendmentPage } from '../../pages/AmendmentPage';
import { loginAsStdAdmin } from '../../utils/auth';
import { FIRST_FREE_AMENDMENT_ID } from '../../utils/constants';
import { replaceInCkEditor, setCkEditorContent } from '../../utils/dom';

test.describe('Amendments: AmendmentsToAmendments', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enable amendments to amendments and create a follow-up amendment', async ({ page }) => {
        await new AmendmentPage(page).open({
            motionSlug: 'Testing_proposed_changes-630',
            amendmentId: 279,
        });
        await expect(page.locator('#sidebar .amendmentCreate')).not.toBeVisible();

        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('.motionType1').click();
        await page.locator('#allowAmendmentsToAmendments').check();
        await page.locator('.adminTypeForm [name="save"]').click();

        await new AmendmentPage(page).open({
            motionSlug: 'Testing_proposed_changes-630',
            amendmentId: 279,
        });
        await page.locator('#sidebar .amendmentCreate').click();

        await expect(page.locator('#sections_2_wysiwyg .ice-ins')).toContainText('A small replacement');
        await expect(page.locator('#sections_2_wysiwyg .ice-del')).toContainText('At vero');
        await expect(page.locator('body')).not.toContainText('The first amendment');

        await replaceInCkEditor(
            page,
            'sections_2_wysiwyg',
            /Stet clita kasd gubergren/,
            'Test 12345678',
        );
        await setCkEditorContent(page, 'amendmentReason_wysiwyg', 'The follow-up amendment');
        await page.locator('#initiatorPrimaryName').fill('A new person');
        await page.locator('#initiatorEmail').fill('test@example.org');

        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();
    });

    test('see the new follow-up amendment', async ({ page }) => {
        await new AmendmentPage(page).open({
            motionSlug: 'Testing_proposed_changes-630',
            amendmentId: 279,
        });
        await expect(
            page.locator(`.amendments .amendment${FIRST_FREE_AMENDMENT_ID}`),
        ).toContainText('Ä5');
        await page.locator(`.amendments .amendment${FIRST_FREE_AMENDMENT_ID}`).click();
        await expect(page.locator('.amendingAmendmentRow')).toContainText('Ä1');
        await expect(page.locator('ins')).toContainText('Test 12345678');
        await expect(page.locator('ins')).toContainText('A small replacement');
        await expect(page.locator('body')).toContainText('The follow-up amendment');
    });
});