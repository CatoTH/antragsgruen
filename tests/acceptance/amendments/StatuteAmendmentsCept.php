<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);

$I->populateDBData1();

$I->wantTo('activate statute amendments and create a base statute');
$I->loginAndGotoStdAdminPage();
$I->click('.motionTypeCreate a');

$I->checkOption('.presetStatute');
$I->fillField('#typeMotionPrefix', 'S');
$I->submitForm('.motionTypeCreateForm', [], 'create');

$I->see('Satzungsänderungsanträge: Basistexte');
$I->seeElement('.baseStatutesNone');
$I->click('.statuteCreateLnk');

$I->fillField('#sections_' . AcceptanceTester::FIRST_FREE_MOTION_SECTION, 'Our statutes');
$sectionId = 'sections_' . (AcceptanceTester::FIRST_FREE_MOTION_SECTION + 1) . '_wysiwyg';
$I->executeJS('CKEDITOR.instances.' . $sectionId . '.setData("<h2>Section 1</h2><ol><li>Article 1</li><li>Article 2</li></ol>");');

$I->submitForm('#motionEditForm', [], 'save');
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->click('.btnBack');

$I->see('Our statutes', '.baseStatutesList .statute' . AcceptanceTester::FIRST_FREE_MOTION_ID);

$I->wantTo('create an amendment');
$I->logout();
$I->loginAsStdUser();
$I->gotoConsultationHome();
$I->click('#sidebar .createMotion' . AcceptanceTester::FIRST_FREE_MOTION_TYPE . ' a');

$I->wait(0.5);
$I->executeJS('window.newText = CKEDITOR.instances.' . $sectionId . '.getData();');
$I->executeJS('window.newText = window.newText.replace(/Article/g, "Paragraph");');
$I->executeJS('CKEDITOR.instances.' . $sectionId . '.setData(window.newText);');
$I->executeJS('CKEDITOR.instances.amendmentReason_wysiwyg.setData("<p>This is my reason</p>");');

$I->fillField(['name' => 'Initiator[primaryName]'], 'My Name');
$I->fillField(['name' => 'Initiator[contactEmail]'], 'test@example.org');
$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');
$I->click('#motionConfirmedForm .btn');

$I->wantTo('check the home pages');
$I->seeElement('.consultationIndex');
$I->see('Our statutes', '.amendmentLink' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->see('S1', '.amendmentLink' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);

$I->logout();
$I->loginAsStdAdmin();

foreach (\app\models\settings\Consultation::getStartLayouts() as $layoutId => $layoutTitle) {
    $page = $I->gotoStdAdminPage()->gotoAppearance();
    $I->selectFueluxOption('#startLayoutType', $layoutId);
    $page->saveForm();
    $I->gotoConsultationHome();
    $I->see('Our statutes', '.amendmentLink' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
    $I->see('S1', '.amendmentLink' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
}

$I->wantTo('check the amendment view');

$I->click('.amendmentLink' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->dontSeeElement('.motionRow');
$I->see('This is my reason');
$I->see('Article 1', '.deleted');
$I->see('Paragraph 1', '.inserted');
$I->see('Zurück zur Übersicht', '#sidebar .back');
$I->click('#sidebar .back a');
$I->seeElement('.consultationIndex');
