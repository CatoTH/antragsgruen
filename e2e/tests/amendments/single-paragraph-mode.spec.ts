import { test, expect } from '../../fixtures';
import { MotionPage } from '../../pages/MotionPage';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { FIRST_FREE_AMENDMENT_ID } from '../../utils/constants';
import { setCkEditorContent } from '../../utils/dom';

test.describe('Amendments: SingleParagraphMode', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('activate single paragraph mode', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('#adminLink').click();
        await page.locator('.motionType1').click();
        await page.locator('#typeAmendSinglePara').check();
        await page.locator('.adminTypeForm [name="save"]').click();
        await expect(page.locator('body')).toContainText('Gespeichert.');
    });

    test('create an amendment', async ({ page }) => {
        await new ConsultationHomePage(page).gotoMotionView(2);
        await page.locator('.amendmentCreate a').click();

        await expect(page.locator('#amendmentReasonHolder .cke_editable')).toBeVisible();
        await expect(page.locator('#section_holder_2')).not.toBeVisible();
        await expect(page.locator('#section_holder_2_0')).toBeVisible();
        await expect(page.locator('#section_holder_2_0 .cke_editable')).toHaveCount(0);
        await expect(page.locator('#section_holder_2_0.modifyable')).toBeVisible();
        await expect(page.locator('#section_holder_2_1.modifyable')).toBeVisible();
    });

    test('click a paragraph and modify it', async ({ page }) => {
        await new ConsultationHomePage(page).gotoMotionView(2);
        await page.locator('.amendmentCreate a').click();

        await page.locator('#section_holder_2_0').click();
        await expect(page.locator('#section_holder_2_0.modifyable')).not.toBeVisible();
        await expect(page.locator('#section_holder_2_0.modified')).toBeVisible();
        await expect(page.locator('#section_holder_2_1.modifyable')).not.toBeVisible();

        await page.locator('#section_holder_2_1').click();
        await expect(page.locator('#section_holder_2_0.modifyable')).not.toBeVisible();
        await expect(page.locator('#section_holder_2_0.modified')).toBeVisible();
        await expect(page.locator('#section_holder_2_1.modifyable')).not.toBeVisible();
        await expect(page.locator('#section_holder_2_1.modified')).not.toBeVisible();
    });

    test('change paragraphs and revert', async ({ page }) => {
        await new ConsultationHomePage(page).gotoMotionView(2);
        await page.locator('.amendmentCreate a').click();

        await setCkEditorContent(page, 'sections_2_0_wysiwyg', '<p>Test 123 ablabl</p>');
        await expect(page.locator('body')).toContainText('Test 123 ablabl');

        await page.locator('#section_holder_2_0 .modifiedActions .revert').click();
        await expect(page.locator('body')).not.toContainText('Test 123 ablabl');
        await expect(page.locator('#section_holder_2_0.modifyable')).toBeVisible();
        await expect(page.locator('#section_holder_2_1.modifyable')).toBeVisible();

        await page.locator('#section_holder_2_1').click();
        await expect(page.locator('#section_holder_2_0.modifyable')).not.toBeVisible();
        await expect(page.locator('#section_holder_2_0.modified')).not.toBeVisible();
        await expect(page.locator('#section_holder_2_1.modifyable')).not.toBeVisible();
        await expect(page.locator('#section_holder_2_1.modified')).toBeVisible();

        await setCkEditorContent(page, 'sections_2_1_wysiwyg', '<p>Test 456</p>');
        await expect(page.locator('body')).toContainText('Test 456');
    });

    test('submit the amendment', async ({ page }) => {
        await new ConsultationHomePage(page).gotoMotionView(2);
        await page.locator('.amendmentCreate a').click();

        await page.locator('#section_holder_2_1').click();
        await setCkEditorContent(page, 'sections_2_1_wysiwyg', '<p>Test 456</p>');
        await page.locator('#initiatorPrimaryName').fill('Mein Name');
        await page.locator('#initiatorEmail').fill('test@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();

        await expect(page.locator('.deleted')).toContainText('Auffi Gamsbart');
        await expect(page.locator('body')).toContainText('Test 456');
    });

    test('correct the amendment', async ({ page }) => {
        await page.locator('#amendmentConfirmForm [name="modify"]').click();
        await expect(page.locator('body')).toContainText('Test 456');

        await expect(page.locator('#section_holder_2_0.modifyable')).not.toBeVisible();
        await expect(page.locator('#section_holder_2_0.modified')).not.toBeVisible();
        await expect(page.locator('#section_holder_2_1.modifyable')).not.toBeVisible();
        await expect(page.locator('#section_holder_2_1.modified')).toBeVisible();

        await page.locator('#section_holder_2_1 .modifiedActions .revert').click();
        await expect(page.locator('body')).not.toContainText('Test 456');
        await expect(page.locator('#section_holder_2_0.modifyable')).toBeVisible();
        await expect(page.locator('#section_holder_2_1.modifyable')).toBeVisible();

        await page.locator('#section_holder_2_0').click();
        await expect(page.locator('#section_holder_2_0.modifyable')).not.toBeVisible();
        await expect(page.locator('#section_holder_2_0.modified')).toBeVisible();
        await expect(page.locator('#section_holder_2_1.modifyable')).not.toBeVisible();

        await setCkEditorContent(page, 'sections_2_0_wysiwyg', '<p>Test 789</p>');
        await expect(page.locator('body')).toContainText('Test 789');

        await page.locator('#amendmentEditForm [name="save"]').click();
        await expect(page.locator('.deleted')).not.toContainText('Auffi Gamsbart');
        await expect(page.locator('body')).not.toContainText('Test 456');
        await expect(page.locator('body')).toContainText('Bavaria ipsum dolor');
        await expect(page.locator('body')).toContainText('Test 789');

        await page.locator('#amendmentConfirmForm [name="confirm"]').click();
    });

    test('check if the amendment is correctly displayed', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await page.locator(`.amendment${FIRST_FREE_AMENDMENT_ID}`).click();
        await expect(page.locator('.deleted')).toContainText('Bavaria ipsum dolor');
        await expect(page.locator('.inserted')).toContainText('Test 789');
        await expect(page.locator('body')).not.toContainText('Test 456');
        await expect(page.locator('body')).not.toContainText('Test 123');
    });

    test('edit the amendment as admin - in full-text mode', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await page.locator('#adminLink').click();
        await page.locator('#motionListLink').click();
        await page
            .locator(`.amendment${FIRST_FREE_AMENDMENT_ID} .edit, .amendment${FIRST_FREE_AMENDMENT_ID} [href*="edit"]`)
            .first()
            .click();

        await expect(page.locator('#amendmentTextEditHolder')).not.toBeVisible();
        await page.evaluate(() => {
            const w = window as any;
            w.$('#amendmentTextEditCaller button').click();
        });
        await expect(page.locator('#amendmentTextEditHolder')).toBeVisible();

        await expect(page.locator('#sections_2_wysiwyg')).toContainText('Test 789');
        await page.locator('#sections_2_wysiwyg').click();

        await setCkEditorContent(page, 'sections_2_wysiwyg', '<p>Test 456</p>');
        await expect(page.locator('body')).toContainText('Test 456');

        await page.locator('#amendmentUpdateForm [name="save"]').click();

        await expect(page.locator('body')).toContainText('Gespeichert.');

        await expect(page.locator('.motionTextHolder .inserted')).toContainText('Test 456');
        await expect(page.locator('.motionTextHolder .deleted')).toContainText(
            'Auffi Gamsbart nimma de Sepp',
        );
    });

    test('create amendment through paragraph-based links', async ({ page }) => {
        await new MotionPage(page).open({ motionSlug: 115 });
        await expect(page.locator('#section_2_5')).toContainText('rhetorische Frage');
        await expect(page.locator('.amendmentParaLink')).not.toBeVisible();
        await page.evaluate(() => {
            const el = document.getElementById('section_2_5');
            if (el) el.classList.add('hover');
        });
        await expect(page.locator('.amendmentParaLink')).toBeVisible();
        await page.evaluate(() => {
            const w = window as any;
            w.$('#section_2_5 .amendmentParaLink').click();
        });

        await expect(page.locator('#section_holder_2_5.modifyable')).not.toBeVisible();
        await expect(page.locator('#section_holder_2_5.modified')).toBeVisible();
        await expect(page.locator('#section_holder_2_1.modifyable')).not.toBeVisible();

        await setCkEditorContent(page, 'sections_2_5_wysiwyg', '<p>Yet another test</p>');
        await expect(page.locator('body')).toContainText('Yet another test');

        await page.locator('#initiatorPrimaryName').fill('My name');
        await page.locator('#initiatorEmail').fill('test@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();
    });
});