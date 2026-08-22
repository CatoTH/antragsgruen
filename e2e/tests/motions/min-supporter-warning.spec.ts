import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { setCkEditorContent, expectBootboxDialog, acceptBootbox } from '../../utils/dom';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';

const PERSON_ORGANIZATION = '1';

test.describe('Minimum supporter warning', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('natural persons get a warning, organizations do not', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open({ subdomain: 'bdk', consultationPath: 'bdk' });
        await loginAsStdAdmin(page);
        await page.locator('#sidebar .createMotion').click();

        await page.locator('[name="sections[20]"]').fill('Testantrag');
        await setCkEditorContent(page, 'sections_21_wysiwyg', '<p><strong>Test</strong></p>');
        await setCkEditorContent(page, 'sections_22_wysiwyg', '<p><strong>Test 2</strong></p>');
        await page.locator('#initiatorPrimaryName').fill('Mein Name');
        await page.locator('#initiatorEmail').fill('test@example.org');
        await page.locator('#motionEditForm [name="save"]').click();

        await expectBootboxDialog(
            page,
            /Es müssen mindestens 19 Unterstützer\*innen angegeben werden/,
        );
        await acceptBootbox(page);

        await page.locator('#personTypeOrga').selectOption(PERSON_ORGANIZATION);
        await page.locator('#initiatorPrimaryName').fill('Meine Organisation');
        await page.locator('#motionEditForm [name="save"]').click();

        await expectBootboxDialog(page, /Es muss ein Beschlussdatum angegeben werden/);
        await acceptBootbox(page);

        await page.locator('#resolutionDate').fill('01.01.2000');
        await page.evaluate(() => {
            const w = window as any;
            w.$('[required]').removeAttr('required');
        });
        await page.locator('#motionEditForm [name="save"]').click();

        await expect(page.locator('body')).not.toContainText('Not enough supporters.');
        await expect(page.locator('h1')).toContainText(/antrag bestätigen/i);
    });
});
