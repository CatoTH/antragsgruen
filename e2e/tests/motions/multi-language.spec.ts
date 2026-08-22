import { test, expect, Page } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { setCkEditorContent, replaceInCkEditor } from '../../utils/dom';
import {
    FIRST_FREE_MOTION_ID,
    FIRST_FREE_MOTION_SECTION,
    FIRST_FREE_MOTION_TYPE,
    FIRST_FREE_AMENDMENT_ID,
} from '../../utils/constants';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';
import { MotionCreatePage } from '../../pages/MotionCreatePage';
import { MotionPage } from '../../pages/MotionPage';
import { AdminMotionPage } from '../../pages/AdminMotionPage';
import { AdminAmendmentPage } from '../../pages/AdminAmendmentPage';

const S_TITLE_DE = FIRST_FREE_MOTION_SECTION;
const S_TITLE_EN = S_TITLE_DE + 1;
const S_TITLE_FR = S_TITLE_DE + 2;
const S_TEXT_DE = S_TITLE_DE + 3;
const S_TEXT_EN = S_TITLE_DE + 4;
const S_TEXT_FR = S_TITLE_DE + 5;
const S_REASON_DE = S_TITLE_DE + 6;
const S_REASON_EN = S_TITLE_DE + 7;
const S_REASON_FR = S_TITLE_DE + 8;

const MOTION_SLUG = String(FIRST_FREE_MOTION_ID);

async function switchLanguage(page: Page, language: string): Promise<void> {
    await page.locator('#logoutLink, #loginLink').first().waitFor({ state: 'visible' });
    const picker = page.locator(`.languagePicker${language} a`);
    if ((await picker.count()) > 0) {
        await picker.click();
    }
}

async function enableThreeLanguages(page: Page): Promise<void> {
    const consultation = new AdminConsultationPage(page);
    await consultation.open();
    await loginAsStdAdmin(page);
    await consultation.open();

    await page.locator('#multiLanguageActivate').click();
    await page.locator('#supportedLanguagede').check();
    await page.locator('#supportedLanguageen').check();
    await page.locator('#supportedLanguagefr').check();
    await consultation.saveForm();

    await expect(page.locator('#supportedLanguagede')).toBeChecked();
    await expect(page.locator('#supportedLanguageen')).toBeChecked();
    await expect(page.locator('#supportedLanguagefr')).toBeChecked();
}

async function createMultiLanguageMotionType(page: Page): Promise<void> {
    await page.locator('#adminLink').click();
    await switchLanguage(page, 'de');
    await page.locator('.motionTypeCreate a').click();
    await page.locator('.presetMotion').check();
    await expect(page.locator('#typeTitleSingular')).toHaveValue('Antrag');
    await page.locator('#typeTitleSingular').fill('Antrag ML');
    await page.locator('#typeTitlePlural').fill('Anträge ML');
    await page.locator('#typeCreateTitle').fill('Antrag ML stellen');
    await page.locator('#typeMotionPrefix').fill('ML');
    await page.locator('.motionTypeCreateForm [name="create"]').click();
    await expect(page.locator('body')).toContainText(
        'Der Antragstyp wurde angelegt. Genauere Einstellungen kannst du nun auf dieser Seite vornehmen.',
    );
}

async function translateMotionTypeLabels(page: Page): Promise<void> {
    await page.locator('#typeCreateSidebar').check();
    await page.locator('#typeTitleSingularen').fill('ML Motion');
    await page.locator('#typeTitlePluralen').fill('ML Motions');
    await page.locator('#typeCreateTitleen').fill('Submit an ML motion');
    await page.locator('#typeTitleSingularfr').fill('Motion ML');
    await page.locator('#typeTitlePluralfr').fill('Motions ML');
    await page.locator('#typeCreateTitlefr').fill('Déposer une motion ML');
    await page.locator('.adminTypeForm [name="save"]').click();
}

async function createEnglishMotion(page: Page): Promise<void> {
    const createPage = new MotionCreatePage(page);
    await createPage.open({ motionTypeId: FIRST_FREE_MOTION_TYPE });

    await page.locator("input[name='tags[]'][value='1']").check();
    await page.locator(`#sections_${S_TITLE_EN}`).fill('My English Motion');
    await setCkEditorContent(page, `sections_${S_TEXT_EN}_wysiwyg`, '<p>English motion text</p>');
    await setCkEditorContent(page, `sections_${S_REASON_EN}_wysiwyg`, '<p>English reason</p>');
    await page.locator('#initiatorPrimaryName').fill('English Submitter');
    await page.locator('#initiatorEmail').fill('mlmotion@example.org');
    await page.locator('#motionEditForm [name="save"]').click();
    await page.locator('#motionConfirmForm [name="confirm"]').click();
}

test.describe('Multi-language motions', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('a language-specific section is created for each translatable slot', async ({ page }) => {
        await enableThreeLanguages(page);
        await createMultiLanguageMotionType(page);

        const expectations: Array<[number, string, string]> = [
            [S_TITLE_DE, 'Titel', 'de'],
            [S_TITLE_EN, 'Title', 'en'],
            [S_TITLE_FR, 'Titre', 'fr'],
            [S_TEXT_DE, 'Antragstext', 'de'],
            [S_TEXT_EN, 'Motion text', 'en'],
            [S_TEXT_FR, 'Texte de la motion', 'fr'],
            [S_REASON_DE, 'Begründung', 'de'],
            [S_REASON_EN, 'Reason', 'en'],
            [S_REASON_FR, 'Justification', 'fr'],
        ];
        for (const [sectionId, title, language] of expectations) {
            await expect(page.locator(`.section${sectionId} .sectionTitle input`)).toHaveValue(
                title,
            );
            await expect(page.locator(`#sectionLanguage${sectionId}`)).toHaveValue(language);
        }
    });

    test('the homepage shows the localized create button in each language', async ({ page }) => {
        await enableThreeLanguages(page);
        await createMultiLanguageMotionType(page);
        await translateMotionTypeLabels(page);
        await logout(page);

        const home = new ConsultationHomePage(page);
        await home.open();
        const button = page.locator(
            `#sidebar .createMotionHolder1 .createMotion${FIRST_FREE_MOTION_TYPE}`,
        );

        await switchLanguage(page, 'de');
        await expect(button).toContainText('Antrag ML stellen');

        await switchLanguage(page, 'en');
        await expect(button).toContainText('Submit an ML motion');

        await switchLanguage(page, 'fr');
        await expect(button).toContainText('Déposer une motion ML');
    });

    test('a motion created in English shows a fallback hint in German', async ({ page }) => {
        await enableThreeLanguages(page);
        await createMultiLanguageMotionType(page);
        await translateMotionTypeLabels(page);
        await logout(page);

        const home = new ConsultationHomePage(page);
        await home.open();
        await switchLanguage(page, 'en');

        const createPage = new MotionCreatePage(page);
        await createPage.open({ motionTypeId: FIRST_FREE_MOTION_TYPE });
        await expect(page.locator(`#sections_${S_TITLE_EN}`)).toBeVisible();
        await expect(page.locator(`#sections_${S_TITLE_DE}`)).toHaveCount(0);
        await expect(page.locator(`#sections_${S_TITLE_FR}`)).toHaveCount(0);

        await createEnglishMotion(page);

        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });
        await switchLanguage(page, 'de');

        await expect(page.locator('body')).toContainText('My English Motion');
        await expect(page.locator('body')).toContainText('English motion text');
        await expect(page.locator('body')).toContainText('English reason');
        await expect(page.locator('.alertLanguageFallback')).toHaveCount(3);
        await expect(page.locator('.alertLanguageFallback').first()).toContainText(
            'Dieser Inhalt wurde noch nicht in deine Sprache übersetzt.',
        );
    });

    test('an admin can translate the motion text into all languages', async ({ page }) => {
        await enableThreeLanguages(page);
        await createMultiLanguageMotionType(page);
        await translateMotionTypeLabels(page);
        await logout(page);

        const home = new ConsultationHomePage(page);
        await home.open();
        await switchLanguage(page, 'en');
        await createEnglishMotion(page);

        await home.open();
        await loginAsStdAdmin(page);
        const adminMotion = new AdminMotionPage(page);
        await adminMotion.open({ motionId: FIRST_FREE_MOTION_ID });
        await page.locator('#motionTextEditCaller button').click();
        await setCkEditorContent(
            page,
            `sections_${S_TEXT_DE}_wysiwyg`,
            '<p>Deutscher Antragstext</p>',
        );
        await setCkEditorContent(
            page,
            `sections_${S_TEXT_FR}_wysiwyg`,
            '<p>Texte de la motion en français</p>',
        );
        await setCkEditorContent(
            page,
            `sections_${S_REASON_DE}_wysiwyg`,
            '<p>Deutsche Begründung</p>',
        );
        await setCkEditorContent(
            page,
            `sections_${S_REASON_FR}_wysiwyg`,
            '<p>Justification en français</p>',
        );
        await page.locator('#motionUpdateForm [name="save"]').click();
        await logout(page);

        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });

        await switchLanguage(page, 'de');
        await expect(page.locator('body')).toContainText('Deutscher Antragstext');
        await expect(page.locator('body')).toContainText('Deutsche Begründung');
        await expect(page.locator('body')).not.toContainText(
            'Dieser Inhalt wurde noch nicht in deine Sprache übersetzt.',
        );

        await switchLanguage(page, 'fr');
        await expect(page.locator('body')).toContainText('Texte de la motion en français');
        await expect(page.locator('body')).toContainText('Justification en français');

        await switchLanguage(page, 'en');
        await expect(page.locator('body')).toContainText('English motion text');
        await expect(page.locator('body')).toContainText('English reason');
    });

    test('an amendment can be created in English and translated by an admin', async ({ page }) => {
        await enableThreeLanguages(page);
        await createMultiLanguageMotionType(page);
        await translateMotionTypeLabels(page);
        await logout(page);

        const home = new ConsultationHomePage(page);
        await home.open();
        await switchLanguage(page, 'en');
        await createEnglishMotion(page);

        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });
        await switchLanguage(page, 'en');
        await page.locator('.sidebarActions .amendmentCreate a').click();

        await expect(page.locator(`#sections_${S_TITLE_EN}`)).toBeVisible();
        await expect(page.locator(`#sections_${S_TITLE_DE}`)).toHaveCount(0);
        await expect(page.locator(`#sections_${S_TITLE_FR}`)).toHaveCount(0);
        await expect(page.locator(`#sections_${S_TEXT_EN}_wysiwyg`)).toBeVisible();
        await expect(page.locator(`#sections_${S_TEXT_DE}_wysiwyg`)).toHaveCount(0);
        await expect(page.locator(`#sections_${S_TEXT_FR}_wysiwyg`)).toHaveCount(0);

        await page.locator(`#sections_${S_TITLE_EN}`).fill('My English Amendment Title');
        await replaceInCkEditor(page, `sections_${S_TEXT_EN}_wysiwyg`, 'motion', 'amended');
        await setCkEditorContent(
            page,
            'amendmentReason_wysiwyg',
            '<p>English amendment reason</p>',
        );
        await page.locator('#initiatorPrimaryName').fill('English Amendment Submitter');
        await page.locator('#initiatorEmail').fill('mlamendment@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();

        await motion.open({ motionSlug: MOTION_SLUG });
        await switchLanguage(page, 'de');
        await page
            .locator(`section.amendments ul.amendments a.amendment${FIRST_FREE_AMENDMENT_ID}`)
            .click();

        await expect(page.locator('body')).toContainText('My English Amendment Title');
        await expect(page.locator(`#section_${S_TEXT_EN} ins`)).toContainText('amended');
        await expect(page.locator('.alertLanguageFallback')).toHaveCount(2);

        await home.open();
        await loginAsStdAdmin(page);
        const adminAmendment = new AdminAmendmentPage(page);
        await adminAmendment.open({ amendmentId: FIRST_FREE_AMENDMENT_ID });
        await page.locator('#amendmentTextEditCaller button').click();
        await page
            .locator(`#sections_${S_TITLE_DE}`)
            .fill('Mein deutscher Änderungsantragstitel');
        await page.locator(`#sections_${S_TITLE_FR}`).fill("Mon titre d'amendement français");
        await replaceInCkEditor(
            page,
            `sections_${S_TEXT_DE}_wysiwyg`,
            'Antragstext',
            'Änderungsantragstext',
        );
        await replaceInCkEditor(page, `sections_${S_TEXT_FR}_wysiwyg`, 'motion', 'amendement');
        await page.locator('#amendmentUpdateForm [name="save"]').click();
        await logout(page);

        await motion.open({ motionSlug: MOTION_SLUG });
        await switchLanguage(page, 'de');
        await page
            .locator(`section.amendments ul.amendments a.amendment${FIRST_FREE_AMENDMENT_ID}`)
            .click();

        await expect(page.locator('body')).toContainText('Mein deutscher Änderungsantragstitel');
        await expect(page.locator(`#section_${S_TEXT_DE} ins`)).toContainText(
            'Änderungsantragstext',
        );
        await expect(page.locator('body')).not.toContainText(
            'Dieser Inhalt wurde noch nicht in deine Sprache übersetzt.',
        );

        await switchLanguage(page, 'fr');
        await expect(page.locator('body')).toContainText("Mon titre d'amendement français");
        await expect(page.locator(`#section_${S_TEXT_FR} ins`)).toContainText('amendement');

        await switchLanguage(page, 'en');
        await expect(page.locator('body')).toContainText('My English Amendment Title');
        await expect(page.locator(`#section_${S_TEXT_EN} ins`)).toContainText('amended');
    });
});
