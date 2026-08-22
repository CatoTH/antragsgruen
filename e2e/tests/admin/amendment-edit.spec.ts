import { test, expect } from '../../fixtures';
import { AmendmentPage } from '../../pages/AmendmentPage';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin } from '../../utils/auth';
import { appendCkEditorContent, setCkEditorContent } from '../../utils/dom';

test.describe('Admin: AmendmentEdit', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('edit an amendment', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await page
            .locator('.amendment1 .edit, .amendment1 [href*="edit"]')
            .first()
            .click();
        await expect(page.locator('body')).toContainText('Lorem ipsum dolor sit amet');
        await expect(page.locator('body')).toContainText('Oamoi a Maß');
        await expect(page.locator('body')).toContainText('Auf gehds beim Schichtl');
        await expect(page.locator('#sections_2')).not.toBeVisible();
        await page.locator('#amendmentTextEditCaller button').click();
        await expect(page.locator('#sections_2')).toBeAttached();

        await page.locator('#amendmentStatus').selectOption('9');
        await page.locator('#amendmentStatusString').fill('völlig erschöpft');
        await page.locator('#amendmentTitlePrefix').fill('Ä1neu');
        await page.locator('#amendmentDateCreation').fill('01.01.2015 01:02');
        await page.locator('#amendmentDateResolution').fill('02.03.2015 04:05');
        await page.locator('#amendmentNoteInternal').fill('Test 123');
        await appendCkEditorContent(page, 'sections_2_wysiwyg', '<p>Test 123</p>');
        await setCkEditorContent(page, 'amendmentReason_wysiwyg', '<p>Another Reason</p>');
        await page.locator('#amendmentUpdateForm [name="save"]').click();

        await page.locator('.sidebarActions .view').click();
        await expect(page.locator('body')).toContainText('Ä1NEU ZU A2');
        await expect(page.locator('body')).toContainText('Erledigt (völlig erschöpft)');
        await expect(page.locator('p.inserted')).toContainText('Test 123');
        await expect(page.locator('body')).toContainText('Another Reason');
        await expect(page.locator('body')).toContainText('02.03.2015');
        await expect(page.locator('body')).toContainText('01.01.2015');
    });

    test('see the changes in the motion list', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await page.locator('#motionListLink').click();
        await expect(page.locator('.amendment1')).toContainText('Ä1neu');
        await expect(page.locator('.amendment1')).toContainText('Erledigt (völlig erschöpft)');
    });
});