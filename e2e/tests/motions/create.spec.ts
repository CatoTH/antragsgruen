import { test, expect } from '../../fixtures';
import {
    logout,
} from '../../utils/auth';
import { setCkEditorContent } from '../../utils/dom';
import {
    FIRST_FREE_MOTION_ID,
    FIRST_FREE_MOTION_TITLE_PREFIX,
} from '../../utils/constants';

test.describe('Motion creation (basic flow)', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('motion Create site loads', async ({ page }) => {
        const home = new (await import('../../pages/ConsultationHomePage')).ConsultationHomePage(page);
        await home.gotoMotionCreatePage();

        await expect(page.locator('h1')).toContainText(/antrag stellen/i);
        await expect(page).toHaveTitle(/antrag stellen/i);
        await expect(page.locator('body')).not.toContainText(
            'Voraussetzungen für einen Antrag',
        );
        await expect(page.locator('label')).toContainText([
            'Überschrift',
            'Antragstext',
            'Begründung',
        ]);

        await expect(page.locator('#personTypeNatural')).toBeChecked();
        await expect(page.locator('#personTypeOrga')).not.toBeChecked();

        await expect(page.locator('body')).not.toContainText('JavaScript aktiviert sein');
        await expect(page.locator('body')).toContainText('Gremium, LAG...');
        await expect(page.locator('body')).not.toContainText('Beschlussdatum');
        await expect(page.locator('body')).not.toContainText('Ansprechperson');

        await page.locator('#personTypeOrga').selectOption('orga');
        await expect(page.locator('body')).not.toContainText('Gremium, LAG...');
        await expect(page.locator('body')).toContainText('Beschlussdatum');
        await expect(page.locator('body')).toContainText('Ansprechperson');
        await expect(page.locator('#section_holder_3 label.optional')).toBeVisible();
        await expect(page.locator('#section_holder_2 label.required')).toBeVisible();
    });

    test('submit without resolution date shows bootbox error', async ({ page }) => {
        const home = new (await import('../../pages/ConsultationHomePage')).ConsultationHomePage(page);
        await home.gotoMotionCreatePage();

        await page.locator("input[name='tags[]'][value='1']").check();
        await page.locator("[name='sections[1]']").fill('Testantrag 1');
        await setCkEditorContent(page, 'sections_2_wysiwyg', '<p><strong>Test</strong></p>');
        await setCkEditorContent(page, 'sections_3_wysiwyg', '<p><strong>Test 2</strong></p>');
        await page.locator("[name='Initiator[primaryName]']").fill('Mein Name');
        await page.locator("[name='Initiator[contactEmail]']").fill('test@example.org');
        await page.locator("[name='Initiator[contactPhone]']").fill('+49123456789');

        await page.locator('#motionEditForm [name="save"]').click();

        const { expectBootboxDialog, acceptBootbox } = await import('../../utils/dom');
        await expectBootboxDialog(page, /Es muss ein Beschlussdatum angegeben werden/);
        await acceptBootbox(page);
    });

    test('create, correct, re-submit, and confirm motion end-to-end', async ({ page }) => {
        const home = new (await import('../../pages/ConsultationHomePage')).ConsultationHomePage(page);
        await home.gotoMotionCreatePage();

        await page.locator("input[name='tags[]'][value='1']").check();
        await page.locator("[name='sections[1]']").fill('Testantrag 1');
        await setCkEditorContent(page, 'sections_2_wysiwyg', '<p><strong>Test</strong></p>');
        await setCkEditorContent(page, 'sections_3_wysiwyg', '<p><strong>Test 2</strong></p>');
        await page.locator("[name='Initiator[primaryName]']").fill('Mein Name');
        await page.locator("[name='Initiator[contactEmail]']").fill('test@example.org');
        await page.locator("[name='Initiator[contactPhone]']").fill('+49123456789');
        await page.locator('#personTypeOrga').selectOption('orga');
        await page.locator("[name='Initiator[resolutionDate]']").fill('12.01.2015');
        await page.locator("[name='Initiator[contactName]']").fill('MeinKontakt');
        await page.locator('#motionEditForm [name="save"]').click();

        await expect(page.locator('h1')).toContainText(/antrag bestätigen/i);

        await page.locator('#motionConfirmForm [name="modify"]').click();
        await expect(page.locator('h1')).toContainText(/antrag stellen/i);

        await page.locator("[name='sections[1]']").fill('Testantrag 2');
        await setCkEditorContent(page, 'sections_2_wysiwyg', '<p><strong>Another string</strong></p>');
        await setCkEditorContent(page, 'sections_3_wysiwyg', '<p><em>Italic is beautiful as well</em></p>');
        await page.locator("[name='Initiator[primaryName]']").fill('');
        await page.locator("[name='Initiator[contactEmail]']").fill('test2@example.org');
        await page.locator("[name='Initiator[contactPhone]']").fill('+49-123-456789');
        await page.locator('#motionEditForm [name="save"]').click();

        await expect(page.locator('body')).toContainText('Bitte gib deinen Namen ein');
        await expect(page.locator('#personTypeNatural')).not.toBeChecked();
        await expect(page.locator('#personTypeOrga')).toBeChecked();

        await page.locator("[name='Initiator[primaryName]']").fill('My real name');
        await page.locator('#motionEditForm [name="save"]').click();

        await expect(page.locator('h1')).toContainText(/testantrag 2/i);
        await expect(page.locator('body')).toContainText('Another string');
        await expect(page.locator('body')).toContainText('Italic is beautiful as well');
        await expect(page.locator('body')).toContainText('My real name');

        await page.locator('#motionConfirmForm [name="confirm"]').click();
        await expect(page.locator('h1')).toContainText(/antrag veröffentlicht/i);
        await expect(page.locator('body')).toContainText(
            'Du hast den Antrag veröffentlicht. Er ist jetzt sofort sichtbar.',
        );

        await page.locator('#motionConfirmedForm [type="submit"]').click();

        await expect(page.locator('body')).toContainText('Hallo auf Antragsgrün');
        await expect(page.locator('body')).toContainText('Testantrag 2');
        await page.locator(`.motionLink${FIRST_FREE_MOTION_ID}`).click();

        await expect(page.locator('h1')).toContainText(
            new RegExp(`${FIRST_FREE_MOTION_TITLE_PREFIX}: Testantrag 2`, 'i'),
        );
        await expect(page.locator('body')).toContainText('My real name');
        await expect(page.locator('body')).not.toContainText('test2@example.org');
        await expect(page.locator('body')).not.toContainText('+49-123-456789');
    });

    test('contact details visible only to admins', async ({ page }) => {
        const { loginAsStdUser, loginAsStdAdmin } = await import('../../utils/auth');
        await loginAsStdUser(page);
        await expect(page.locator('body')).not.toContainText('test2@example.org');
        await expect(page.locator('body')).not.toContainText('+49-123-456789');

        await logout(page);
        await loginAsStdAdmin(page);
        await expect(page.locator('body')).toContainText('My real name');
        await expect(page.locator('body')).not.toContainText('test2@example.org');
        await page.locator('.contactShow').click();
        await expect(page.locator('body')).toContainText('test2@example.org');
        await expect(page.locator('body')).toContainText('+49-123-456789');
    });
});