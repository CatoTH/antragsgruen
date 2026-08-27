import { test, expect } from '../../fixtures';
import { setCkEditorContent } from '../../utils/dom';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';

test.describe('Application creation', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('open the application form and apply for a job', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open({ subdomain: 'parteitag', consultationPath: 'parteitag' });

        await expect(page.locator('#agendaitem_3')).toContainText('1. Vorsitzende*r');
        await expect(page.locator('#agendaitem_3 > div > h3 .motionCreateLink').first()).toBeVisible();
        await page.locator('#agendaitem_3 > div > h3 .motionCreateLink').click();

        await expect(page.locator('.breadcrumb')).toContainText(/bewerben/i);
        await expect(page.locator('h1')).toContainText(/1\. Vorsitzende\*r: Bewerben/i);

        await expect(page.locator('body')).not.toContainText('Voraussetzungen für einen Antrag', { useInnerText: true });
        await expect(page.locator('label')).toContainText([
            'Name',
            'Foto',
            'Angaben',
            'Selbstvorstellung',
        ]);

        await page.locator('#sections_13').first().fill('Jane Doe');
        await page.locator('#sections_14').setInputFiles('tests/Support/Data/logo.png');
        await page.locator('#sections_15_1').first().fill('23');
        await page.locator('#sections_15_2').first().fill('Female');
        await page.locator('#sections_15_3').first().fill('Somewhere');
        await setCkEditorContent(page, 'sections_16_wysiwyg', '<p><strong>Test</strong></p>');
        await page.locator('#initiatorPrimaryName').first().fill('Jane Doe (2)');
        await page.locator('#initiatorEmail').first().fill('jane@example.org');

        await page.locator('#motionEditForm [name="save"]').click();
        await page.locator('#motionConfirmForm [name="confirm"]').click();
        await page.locator('#motionConfirmedForm [type="submit"]').click();
    });
});
