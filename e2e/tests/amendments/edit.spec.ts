import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { appendCkEditorContent } from '../../utils/dom';

test.describe('Amendments: Edit', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enable editing of amendments and edit an amendment as a regular user', async ({
        page,
    }) => {
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('#consultationLink').click();
        await page.locator('#iniatorsMayEdit').check();
        await page.locator('#consultationSettingsForm [name="save"]').click();
        await logout(page);

        await new ConsultationHomePage(page).open();
        await loginAsStdUser(page);
        await page.locator('.myAmendmentList .amendment2').click();
        await page.locator('.sidebarActions .edit a').click();
        await expect(page.locator('h1')).toContainText(
            'ÄNDERUNGSANTRAG ZU A3: TEXTFORMATIERUNGEN BEARBEITEN',
        );

        await appendCkEditorContent(page, 'sections_2_wysiwyg', '<p>attach some new text at the end</p>');
        await page.locator('#amendmentEditForm [name="save"]').click();

        await expect(page.locator('body')).toContainText('Die Änderungen wurden übernommen');
        await page.locator('#motionConfirmedForm button').click();
        await expect(page.locator('p.inserted')).toContainText('attach some new text at the end');
    });
});