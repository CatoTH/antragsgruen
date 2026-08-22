import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { replaceInCkEditor, setCkEditorContent, expectBootboxDialog, acceptBootbox } from '../../utils/dom';

test.describe('Amendments: DraftSave', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('fill in text, leave page, see and restore the draft', async ({ page }) => {
        await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');

        await replaceInCkEditor(page, 'sections_2_wysiwyg', /woschechta Bayer/g, 'El Capitan');
        await setCkEditorContent(page, 'amendmentReason_wysiwyg', '<p>This is my reason</p>');

        await new ConsultationHomePage(page).open();

        await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');
        await expect(page.locator('#draftHint')).toBeVisible();
        await expect(page.locator('#draftHint')).toContainText('Entwurf vom:');
        await expect(page.locator("input[name='sections[1]']")).not.toHaveValue('Draft title');
        await expect(page.locator('body')).not.toContainText('Some text');
        await expect(page.locator('body')).not.toContainText('Even more text');

        await page.locator('#draftHint button.restore').click();
        await expectBootboxDialog(page, /Diesen Entwurf wiederherstellen/);
        await acceptBootbox(page);
        await expect(page.locator('body')).toContainText('El Capitan');
        await expect(page.locator('body')).toContainText('This is my reason');
        await expect(page.locator('#draftHint')).not.toBeVisible();
    });
});