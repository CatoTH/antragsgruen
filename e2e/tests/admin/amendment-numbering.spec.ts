import { test, expect } from '../../fixtures';
import { MotionPage } from '../../pages/MotionPage';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { FIRST_FREE_AMENDMENT_ID } from '../../utils/constants';
import { loginAsStdAdmin } from '../../utils/auth';
import { replaceInCkEditor, setCkEditorContent } from '../../utils/dom';

test.describe('Admin: AmendmentNumbering', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('numbering amendments by line', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).open();
        await page.locator('#adminLink').click();
        await page.locator('#consultationLink').click();
        await page.locator('#amendmentNumbering').first().selectOption('1');
        await page.locator('#consultationSettingsForm [name="save"]').click();

        await new ConsultationHomePage(page).open();
        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await page.locator('.sidebarActions .amendmentCreate a').click();

        await replaceInCkEditor(page, 'sections_2_wysiwyg', /woschechta Bayer/g, 'Saupreiß');
        await setCkEditorContent(page, 'amendmentReason_wysiwyg', '<p>This is my reason</p>');

        await page.locator('#initiatorPrimaryName').first().fill('My Name');
        await page.locator('#initiatorEmail').first().fill('test@example.org');

        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();

        await new ConsultationHomePage(page).open();
        await expect(page.locator(`.amendment${FIRST_FREE_AMENDMENT_ID}`)).toContainText('A2-003');
    });
});