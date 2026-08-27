import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { appendCkEditorContent } from '../../utils/dom';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';

test.describe('Motion editing', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('an initiator can edit their motion text', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);

        const consultation = new AdminConsultationPage(page);
        await consultation.open();
        await test.step('enable editing of motions', async () => {
            await page.locator('#iniatorsMayEdit').first().check();
            await consultation.saveForm();
            await logout(page);

            await home.open();
            await loginAsStdUser(page);
        });

        await test.step('edit an motion', async () => {
            await page.locator('.myMotionList .motion58').click();
            await page.locator('.sidebarActions .edit a').click();
            await expect(page.locator('h1')).toContainText('Antrag bearbeiten');

            await appendCkEditorContent(
                page,
                'sections_2_wysiwyg',
                '<p>attach some new text at the end</p>',
            );
            await page.locator('#motionEditForm [name="save"]').click();

            await expect(page.locator('body')).toContainText('Die Änderungen wurden übernommen');
            await page.locator('#motionConfirmedForm button').click();
            await expect(page.locator('.motionTextHolder').first()).toContainText(
                'attach some new text at the end',
            );
        });
    });
});
