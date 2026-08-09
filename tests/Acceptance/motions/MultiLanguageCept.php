<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

/**
 * Forces the browsing language via the navbar language picker - a no-op when we're already on that
 * language, since the picker only lists languages *other* than the current one. The very first page
 * load after multi-language is enabled auto-resolves an unset session language from the browser's
 * Accept-Language header (see LanguageTools::resolveCurrentLanguage()) and remembers it for the rest
 * of the browser session, so which language is "current" at any point can otherwise not be assumed.
 * Waits for the navbar to be present first, so this is safe to call right after a navigation/submit
 * whose resulting page hasn't necessarily finished loading yet.
 */
$switchLanguage = function (string $language) use ($I): void {
    $I->waitForElement('#logoutLink, #loginLink', 10);
    $exists = $I->executeJS('return !!document.querySelector(".languagePicker' . $language . ' a");');
    if ($exists) {
        $I->click('.languagePicker' . $language . ' a');
        $I->wait(0.5);
    }
};

$typeId = AcceptanceTester::FIRST_FREE_MOTION_TYPE;

$sTitleDe   = AcceptanceTester::FIRST_FREE_MOTION_SECTION;
$sTitleEn   = $sTitleDe + 1;
$sTitleFr   = $sTitleDe + 2;
$sTextDe    = $sTitleDe + 3;
$sTextEn    = $sTitleDe + 4;
$sTextFr    = $sTitleDe + 5;
$sReasonDe  = $sTitleDe + 6;
$sReasonEn  = $sTitleDe + 7;
$sReasonFr  = $sTitleDe + 8;

$motionId = AcceptanceTester::FIRST_FREE_MOTION_ID;


$I->wantTo('enable German, English and French for stdparteitag');
$I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->click('#multiLanguageActivate');
$I->checkOption('#supportedLanguagede');
$I->checkOption('#supportedLanguageen');
$I->checkOption('#supportedLanguagefr');
$I->submitForm('#consultationSettingsForm', [], 'save');
$I->seeCheckboxIsChecked('#supportedLanguagede');
$I->seeCheckboxIsChecked('#supportedLanguageen');
$I->seeCheckboxIsChecked('#supportedLanguagefr');


$I->wantTo('create a new motion type from the motion template');
$I->click('#adminLink');
// The settings-save response above is rendered from a request that pre-dates the save, so it may
// still reflect the site as single-language and not yet offer a language picker to force German
// with - this first fresh page load after the save is the earliest reliable point to do so.
$switchLanguage('de');
$I->click('.motionTypeCreate a');
$I->checkOption('.presetMotion');
$I->seeInField('#typeTitleSingular', 'Antrag');
$I->fillField('#typeTitleSingular', 'Antrag ML');
$I->fillField('#typeTitlePlural', 'Anträge ML');
$I->fillField('#typeCreateTitle', 'Antrag ML stellen');
$I->fillField('#typeMotionPrefix', 'ML');
$I->submitForm('.motionTypeCreateForm', [], 'create');
$I->see('Der Antragstyp wurde angelegt. Genauere Einstellungen kannst du nun auf dieser Seite vornehmen.');


$I->wantTo('confirm that a language-specific section was created for every translatable slot');
$I->seeInField('.section' . $sTitleDe . ' .sectionTitle input', 'Titel');
$I->seeInField('#sectionLanguage' . $sTitleDe, 'de');
$I->seeInField('.section' . $sTitleEn . ' .sectionTitle input', 'Title');
$I->seeInField('#sectionLanguage' . $sTitleEn, 'en');
$I->seeInField('.section' . $sTitleFr . ' .sectionTitle input', 'Titre');
$I->seeInField('#sectionLanguage' . $sTitleFr, 'fr');
$I->seeInField('.section' . $sTextDe . ' .sectionTitle input', 'Antragstext');
$I->seeInField('#sectionLanguage' . $sTextDe, 'de');
$I->seeInField('.section' . $sTextEn . ' .sectionTitle input', 'Motion text');
$I->seeInField('#sectionLanguage' . $sTextEn, 'en');
$I->seeInField('.section' . $sTextFr . ' .sectionTitle input', 'Texte de la motion');
$I->seeInField('#sectionLanguage' . $sTextFr, 'fr');
$I->seeInField('.section' . $sReasonDe . ' .sectionTitle input', 'Begründung');
$I->seeInField('#sectionLanguage' . $sReasonDe, 'de');
$I->seeInField('.section' . $sReasonEn . ' .sectionTitle input', 'Reason');
$I->seeInField('#sectionLanguage' . $sReasonEn, 'en');
$I->seeInField('.section' . $sReasonFr . ' .sectionTitle input', 'Justification');
$I->seeInField('#sectionLanguage' . $sReasonFr, 'fr');


$I->wantTo('translate the motion type labels into English and French, and highlight its create button');
$I->checkOption('#typeCreateSidebar');
$I->fillField('#typeTitleSingularen', 'ML Motion');
$I->fillField('#typeTitlePluralen', 'ML Motions');
$I->fillField('#typeCreateTitleen', 'Submit an ML motion');
$I->fillField('#typeTitleSingularfr', 'Motion ML');
$I->fillField('#typeTitlePluralfr', 'Motions ML');
$I->fillField('#typeCreateTitlefr', 'Déposer une motion ML');
$I->submitForm('.adminTypeForm', [], 'save');
$I->seeInField('#typeTitleSingularen', 'ML Motion');
$I->seeInField('#typeTitlePluralen', 'ML Motions');
$I->seeInField('#typeCreateTitleen', 'Submit an ML motion');
$I->seeInField('#typeTitleSingularfr', 'Motion ML');
$I->seeInField('#typeTitlePluralfr', 'Motions ML');
$I->seeInField('#typeCreateTitlefr', 'Déposer une motion ML');

$I->logout();


$I->wantTo('check the homepage in all three languages, including the call to create a new motion');
$I->gotoConsultationHome();

$switchLanguage('de');
$I->see('Antrag ML stellen', '#sidebar .createMotionHolder1 .createMotion' . $typeId);

$switchLanguage('en');
$I->see('Submit an ML motion', '#sidebar .createMotionHolder1 .createMotion' . $typeId);

$switchLanguage('fr');
$I->see('Déposer une motion ML', '#sidebar .createMotionHolder1 .createMotion' . $typeId);


$I->wantTo('create a motion in English');
$switchLanguage('en');
$I->gotoMotionCreatePage(motionTypeId: $typeId);

$I->seeElement('#sections_' . $sTitleEn);
$I->dontSeeElement('#sections_' . $sTitleDe);
$I->dontSeeElement('#sections_' . $sTitleFr);

$I->checkOption("//input[@name='tags[]'][@value='1']");
$I->fillField('#sections_' . $sTitleEn, 'My English Motion');
$I->executeJS('CKEDITOR.instances.sections_' . $sTextEn . '_wysiwyg.setData("<p>English motion text</p>");');
$I->executeJS('CKEDITOR.instances.sections_' . $sReasonEn . '_wysiwyg.setData("<p>English reason</p>");');
$I->fillField('#initiatorPrimaryName', 'English Submitter');
$I->fillField('#initiatorEmail', 'mlmotion@example.org');
$I->submitForm('#motionEditForm', [], 'save');
$I->submitForm('#motionConfirmForm', [], 'confirm');


$I->wantTo('view the just-created motion in German and see the not-yet-translated hint');
$I->gotoMotion(true, (string) $motionId);
$switchLanguage('de');

$I->see('My English Motion');
$I->see('English motion text');
$I->see('English reason');
$I->seeNumberOfElements('.alertLanguageFallback', 3);
$I->see('Dieser Inhalt wurde noch nicht in deine Sprache übersetzt.', '.alertLanguageFallback');


$I->wantTo('as an admin, translate the motion text and reason into German and French');
// The title section is a known limitation (see multilanguage-implementation.md §16): the admin
// metadata screen only edits the motion's single canonical title cache, not the per-language title
// sections, so it stays untranslated here - this covers the text/reason sections, which the generic
// per-section admin edit form does support.
$I->loginAndGotoMotionList()->gotoMotionEdit($motionId);
$I->clickJS('#motionTextEditCaller button');
$I->executeJS('CKEDITOR.instances.sections_' . $sTextDe . '_wysiwyg.setData("<p>Deutscher Antragstext</p>");');
$I->executeJS('CKEDITOR.instances.sections_' . $sTextFr . '_wysiwyg.setData("<p>Texte de la motion en français</p>");');
$I->executeJS('CKEDITOR.instances.sections_' . $sReasonDe . '_wysiwyg.setData("<p>Deutsche Begründung</p>");');
$I->executeJS('CKEDITOR.instances.sections_' . $sReasonFr . '_wysiwyg.setData("<p>Justification en français</p>");');
$I->submitForm('#motionUpdateForm', [], 'save');

$I->logout();


$I->wantTo('go to the regular motion view and see the translated sections in every language');
$I->gotoMotion(true, (string) $motionId);

$switchLanguage('de');
$I->see('Deutscher Antragstext');
$I->see('Deutsche Begründung');
$I->dontSee('Dieser Inhalt wurde noch nicht in deine Sprache übersetzt.');

$switchLanguage('fr');
$I->see('Texte de la motion en français');
$I->see('Justification en français');

$switchLanguage('en');
$I->see('English motion text');
$I->see('English reason');


$amendmentId = AcceptanceTester::FIRST_FREE_AMENDMENT_ID;

$I->wantTo('create an amendment in English');
// Already browsing in English (and logged out) from the check just above.
$I->click('.sidebarActions .amendmentCreate a');

$I->seeElement('#sections_' . $sTitleEn);
$I->dontSeeElement('#sections_' . $sTitleDe);
$I->dontSeeElement('#sections_' . $sTitleFr);
$I->seeElement('#sections_' . $sTextEn . '_wysiwyg');
$I->dontSeeElement('#sections_' . $sTextDe . '_wysiwyg');
$I->dontSeeElement('#sections_' . $sTextFr . '_wysiwyg');

// The amendment text field is pre-filled with the original motion text, ICE-tracked for diffing -
// a wholesale setData() of unrelated content would show as "everything deleted, everything inserted"
// but still render fine; a targeted word replacement (matching how existing amendment Cepts, e.g.
// CreateCept, edit amendment text) keeps the diff - and therefore the assertions below - readable.
$I->fillField('#sections_' . $sTitleEn, 'My English Amendment Title');
$I->executeJS(
    'var t = CKEDITOR.instances.sections_' . $sTextEn . '_wysiwyg.getData();' .
    'CKEDITOR.instances.sections_' . $sTextEn . '_wysiwyg.setData(t.replace("motion", "amended"));'
);
$I->executeJS('CKEDITOR.instances.amendmentReason_wysiwyg.setData("<p>English amendment reason</p>");');
$I->fillField('#initiatorPrimaryName', 'English Amendment Submitter');
$I->fillField('#initiatorEmail', 'mlamendment@example.org');
$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');


$I->wantTo('view the just-created amendment in German and see the not-yet-translated hint');
$I->gotoMotion(true, (string) $motionId);
$switchLanguage('de');
$I->click('section.amendments ul.amendments a.amendment' . $amendmentId);

$I->see('My English Amendment Title');
$I->see('amended', '#section_' . $sTextEn . ' ins');
$I->seeNumberOfElements('.alertLanguageFallback', 2);
$I->see('Dieser Inhalt wurde noch nicht in deine Sprache übersetzt.', '.alertLanguageFallback');


$I->wantTo('as an admin, translate the amendment title and text into German and French');
$I->loginAndGotoMotionList()->gotoAmendmentEdit($amendmentId);
$I->clickJS('#amendmentTextEditCaller button');
$I->fillField('#sections_' . $sTitleDe, 'Mein deutscher Änderungsantragstitel');
$I->fillField('#sections_' . $sTitleFr, 'Mon titre d\'amendement français');
$I->executeJS(
    'var t = CKEDITOR.instances.sections_' . $sTextDe . '_wysiwyg.getData();' .
    'CKEDITOR.instances.sections_' . $sTextDe . '_wysiwyg.setData(t.replace("Antragstext", "Änderungsantragstext"));'
);
$I->executeJS(
    'var t = CKEDITOR.instances.sections_' . $sTextFr . '_wysiwyg.getData();' .
    'CKEDITOR.instances.sections_' . $sTextFr . '_wysiwyg.setData(t.replace("motion", "amendement"));'
);
$I->submitForm('#amendmentUpdateForm', [], 'save');

$I->logout();


$I->wantTo('go to the regular amendment view and see the translated sections in every language');
$I->gotoMotion(true, (string) $motionId);
$switchLanguage('de');
$I->click('section.amendments ul.amendments a.amendment' . $amendmentId);

$I->see('Mein deutscher Änderungsantragstitel');
$I->see('Änderungsantragstext', '#section_' . $sTextDe . ' ins');
$I->dontSee('Dieser Inhalt wurde noch nicht in deine Sprache übersetzt.');

$switchLanguage('fr');
$I->see('Mon titre d\'amendement français');
$I->see('amendement', '#section_' . $sTextFr . ' ins');

$switchLanguage('en');
$I->see('My English Amendment Title');
$I->see('amended', '#section_' . $sTextEn . ' ins');
