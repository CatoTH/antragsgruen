import { test, expect } from '../../fixtures';
import { MotionPage } from '../../pages/MotionPage';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import {
    FIRST_FREE_AMENDMENT_ID,
    FIRST_FREE_AMENDMENT_TITLE_PREFIX,
} from '../../utils/constants';
import {
    expectBootboxDialog,
    acceptBootbox,
    replaceInCkEditor,
    setCkEditorContent,
} from '../../utils/dom';

test.describe('Amendments: Create', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('ensure the amendment does not exist yet and open creation', async ({ page }) => {
        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('h1')).toContainText('A2: O’ZAPFT IS!');
        await expect(page.locator('section.amendments ul.amendments')).not.toContainText(
            FIRST_FREE_AMENDMENT_TITLE_PREFIX,
        );

        await expect(page.locator('.sidebarActions')).toContainText('ÄNDERUNGSANTRAG STELLEN');
        await page.locator('.sidebarActions .amendmentCreate a').click();

        await expect(page.locator('.breadcrumb')).toContainText('Antrag');
        await expect(page.locator('h1')).toContainText(
            'ÄNDERUNGSANTRAG ZU A2: O’ZAPFT IS! STELLEN',
        );
        await expect(page.locator('#sections_1')).toHaveValue('O’zapft is!');
        await expect(page.locator('#section_holder_2')).toContainText('woschechta Bayer');
    });

    test('modify the motion text and trigger bootbox error', async ({ page }) => {
        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await page.locator('.sidebarActions .amendmentCreate a').click();

        await expect(page.locator('body')).not.toContainText('JavaScript aktiviert sein');
        await expect(page.locator('body')).toContainText('Gremium, LAG...');
        await expect(page.locator('body')).not.toContainText('Beschlussdatum');
        await expect(page.locator('body')).not.toContainText('Ansprechperson');
        await page.locator('#personTypeOrga').selectOption('orga');
        await expect(page.locator('body')).not.toContainText('Gremium, LAG...');
        await expect(page.locator('body')).toContainText('Beschlussdatum');
        await expect(page.locator('body')).toContainText('Ansprechperson');

        await replaceInCkEditor(page, 'sections_2_wysiwyg', /woschechta Bayer/g, 'Saupreiß');
        await setCkEditorContent(page, 'amendmentReason_wysiwyg', '<p>This is my reason</p>');

        await expect(page.locator('#section_holder_2')).not.toContainText('woschechta Bayer');
        await expect(page.locator('#section_holder_2')).toContainText('Saupreiß');

        await page.locator('#sections_1').fill('New title');

        await expect(page.locator('.editorialChange .wysiwyg-textarea')).not.toBeVisible();
        await page.evaluate(() => {
            const w = window as any;
            w.$('input[name=editorialChange]').prop('checked', true).change();
        });
        await expect(page.locator('#sectionHolderEditorial')).toBeVisible();
        await setCkEditorContent(page, 'amendmentEditorial_wysiwyg', '<p>some meta text</p>');

        await page.locator("input[name='Initiator[primaryName]']").fill('My Name');
        await page.locator("input[name='Initiator[contactEmail]']").fill('test@example.org');
        await page.locator('#personTypeOrga').selectOption('orga');
        await page.locator('#amendmentEditForm [name="save"]').click();

        await expectBootboxDialog(page, /Es muss ein Beschlussdatum angegeben werden/);
        await acceptBootbox(page);

        await expect(page.locator('#sections_1')).toHaveValue('New title');
        await expect(page.locator('#section_holder_2')).toContainText('Saupreiß');
        await expect(page.locator('#amendmentReasonHolder')).toContainText('This is my reason');

        await expect(page.locator("input[name='Initiator[primaryName]']")).toHaveValue('My Name');
        await expect(page.locator("input[name='Initiator[contactEmail]']")).toHaveValue('test@example.org');
        await expect(page.locator('#personTypeNatural')).not.toBeChecked();
        await expect(page.locator('#personTypeOrga')).toBeChecked();
        await expect(page.locator('body')).not.toContainText('Gremium, LAG...');
        await expect(page.locator('body')).toContainText('Beschlussdatum');
    });

    test('enter the missing data and submit the amendment', async ({ page }) => {
        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await page.locator('.sidebarActions .amendmentCreate a').click();

        await expect(page.locator('.bootstrap-datetimepicker-widget')).not.toBeVisible();
        await page.evaluate(() => {
            const w = window as any;
            w.$('#resolutionDateHolder').find('.input-group-addon').click();
        });
        await expect(page.locator('.bootstrap-datetimepicker-widget')).toBeVisible();
        await page.evaluate(() => {
            const w = window as any;
            w.$('#resolutionDateHolder').find('.input-group-addon').click();
        });
        await expect(page.locator('.bootstrap-datetimepicker-widget')).not.toBeVisible();

        await page.locator("input[name='Initiator[primaryName]']").fill('My company');
        await page.locator("input[name='Initiator[resolutionDate]']").fill('12.01.2015');
        await page.locator("input[name='Initiator[contactName]']").fill('MeinKontakt');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await expect(page.locator('h1')).toContainText('ÄNDERUNGSANTRAG BESTÄTIGEN');
    });

    test('not confirm, instead correcting a mistake', async ({ page }) => {
        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await page.locator('.sidebarActions .amendmentCreate a').click();

        await page.locator("input[name='Initiator[primaryName]']").fill('My company');
        await page.locator("input[name='Initiator[resolutionDate]']").fill('12.01.2015');
        await page.locator("input[name='Initiator[contactName]']").fill('MeinKontakt');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="modify"]').click();
        await expect(page.locator('h1')).toContainText(
            'ÄNDERUNGSANTRAG ZU A2: O’ZAPFT IS! STELLEN',
        );
        await expect(page.locator("input[name='Initiator[primaryName]']")).toHaveValue('My company');
        await expect(page.locator("input[name='Initiator[contactName]']")).toHaveValue('MeinKontakt');
        await expect(page.locator("input[name='Initiator[resolutionDate]']")).toHaveValue(
            '12.01.2015',
        );
        await expect(page.locator('#sectionHolderEditorial')).toContainText('some meta text');

        await setCkEditorContent(
            page,
            'amendmentReason_wysiwyg',
            '<p>This is my extended reason</p>',
        );
        await page.locator('#amendmentEditForm [name="save"]').click();
        await expect(page.locator('h1')).toContainText('ÄNDERUNGSANTRAG BESTÄTIGEN');
        await expect(page.locator('.amendmentReasonHolder')).toContainText(
            'This is my extended reason',
        );
        await expect(page.locator('body')).toContainText('some meta text');
    });

    test('submit the final amendment', async ({ page }) => {
        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await page.locator('.sidebarActions .amendmentCreate a').click();

        await page.locator('#sections_1').fill('New title');
        await page.locator("input[name='Initiator[primaryName]']").fill('My company');
        await page.locator("input[name='Initiator[resolutionDate]']").fill('12.01.2015');
        await page.locator("input[name='Initiator[contactName]']").fill('MeinKontakt');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();
        await expect(page.locator('h1')).toContainText('ÄNDERUNGSANTRAG VERÖFFENTLICHT');
        await expect(page.locator('body')).toContainText(
            'Du hast den Änderungsantrag veröffentlicht. Er ist jetzt sofort sichtbar.',
        );
    });

    test('see the amendment on the start page and the motion page', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await expect(page.locator('.motionListStd .amendments')).toContainText(
            FIRST_FREE_AMENDMENT_TITLE_PREFIX,
        );
        await expect(page.locator('.motionListStd .amendments')).toContainText('My company');

        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('h1')).toContainText('A2: O’ZAPFT IS!');
        await expect(page.locator('section.amendments ul.amendments')).toContainText(
            FIRST_FREE_AMENDMENT_TITLE_PREFIX,
        );

        await page.locator(`section.amendments ul.amendments a.amendment${FIRST_FREE_AMENDMENT_ID}`).click();

        await expect(page.locator('h1')).toContainText(
            `${FIRST_FREE_AMENDMENT_TITLE_PREFIX} ZU A2: O’ZAPFT IS!`,
        );
        await expect(page.locator('.motionDataTable')).toContainText('My company');
        await expect(page.locator('#section_2_0 del')).toContainText('woschechta Bayer');
        await expect(page.locator('#section_2_0 ins')).toContainText('Saupreiß');
        await expect(page.locator('#amendmentExplanation')).toContainText(
            'This is my extended reason',
        );
        await expect(page.locator('body')).toContainText('some meta text');
    });
});