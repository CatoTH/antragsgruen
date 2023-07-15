<?php

/** @var \Codeception\Scenario $scenario */
use app\models\settings\Consultation;
use Tests\Support\AcceptanceTester;

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

$I->dontSeeElement('#typePolicySupportMotions');
$I->seeElement('#typePolicySupportAmendments');
$I->dontSeeElement('#motionSupportersForm');
$I->seeElement('#amendmentSupportersForm');

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

$I->dontSee('Our statutes');

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

$I->logout();
$I->loginAsStdAdmin();


$I->wantTo('set up an agenda and assign the statute amendment to it');

$page = $I->gotoStdAdminPage()->gotoAppearance();
$I->selectOption('#startLayoutType', '4');
$page->saveForm();

$I->gotoConsultationHome();

$I->executeJS('$(".agendaItemAdder").last().find("a.addEntry").click()');
$I->executeJS('$(".agendaItemEditForm").last().find(".title input").val("Earth");');
$I->executeJS('$(".agendaItemEditForm").last().trigger("submit");');

$I->executeJS('$(".agendaItemAdder").last().find("a.addEntry").click()');
$I->executeJS('$(".agendaItemEditForm").last().find(".title input").val("Mars");');
$I->executeJS('$(".agendaItemEditForm").last().trigger("submit");');

$I->executeJS('$(".agendaItemAdder").last().find("a.addEntry").click()');
$I->executeJS('$(".agendaItemEditForm").last().find(".title input").val("venus");');
$I->executeJS('$(".agendaItemEditForm").last().trigger("submit");');

$earth = AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID;
$mars = AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID + 1;
$venus = AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID + 2;


$I->wantTo('check the home pages (with no agenda item assigned)');
$I->seeElement('.consultationIndex');
$I->see('Our statutes', '.amendmentLink' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->see('S1', '.amendmentLink' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);

foreach (Consultation::getStartLayouts() as $layoutId => $layoutTitle) {
    $page = $I->gotoStdAdminPage()->gotoAppearance();
    $I->selectOption('#startLayoutType', $layoutId);
    $page->saveForm();
    $I->gotoConsultationHome();
    $I->see('Our statutes', '.amendmentLink' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
    $I->see('S1', '.amendmentLink' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
}


$I->wantTo('check the home pages (with an agenda item assigned)');

$I->gotoMotionList()->gotoAmendmentEdit(AcceptanceTester::FIRST_FREE_AMENDMENT_ID);

$I->selectOption('#agendaItemId', $earth);
$I->submitForm('#amendmentUpdateForm', [], 'save');

$page = $I->gotoStdAdminPage()->gotoAppearance();
$I->selectOption('#startLayoutType', '3');
$page->saveForm();
$I->gotoConsultationHome();
$I->see('Our statutes', '#agendaitem_' . $earth . ' .amendmentLink' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->see('S1', '#agendaitem_' . $earth . ' .amendmentLink' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);


$page = $I->gotoStdAdminPage()->gotoAppearance();
$I->selectOption('#startLayoutType', '4');
$page->saveForm();
$I->gotoConsultationHome();
$I->see('Our statutes', '.agenda' . $earth . ' .amendmentLink' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->see('S1', '.agenda' . $earth . ' .amendmentLink' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);


$I->wantTo('check the amendment view');

$I->click('.amendmentLink' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->dontSeeElement('.motionRow');
$I->see('This is my reason');
$I->see('Article 1', '.deleted');
$I->see('Paragraph 1', '.inserted');
$I->see('Zurück zur Übersicht', '#sidebar .back');
$I->click('#sidebar .back a');
$I->seeElement('.consultationIndex');


$I->wantTo('check what happens if there are two statutes and amendments');
$I->gotoStdAdminPage()->gotoMotionTypes(AcceptanceTester::FIRST_FREE_MOTION_TYPE);
$I->seeElement('.baseStatutesList');
$I->click('.statuteCreateLnk');
$I->fillField('#sections_' . AcceptanceTester::FIRST_FREE_MOTION_SECTION, 'Our second statutes');
$sectionId = 'sections_' . (AcceptanceTester::FIRST_FREE_MOTION_SECTION + 1) . '_wysiwyg';
$I->executeJS('CKEDITOR.instances.' . $sectionId . '.setData("<h2>Another part of the statutes</h2>");');

$I->submitForm('#motionEditForm', [], 'save');
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->click('.btnBack');

$I->gotoConsultationHome();
$I->click('#sidebar .createMotion' . AcceptanceTester::FIRST_FREE_MOTION_TYPE . ' a');
$I->seeElement('.createSelectStatutes');
$I->click('.statute' . (AcceptanceTester::FIRST_FREE_MOTION_ID + 1) . ' a');

$I->wait(0.5);
$I->executeJS('CKEDITOR.instances.' . $sectionId . '.setData("<p>A completely different text</p>");');
$I->executeJS('CKEDITOR.instances.amendmentReason_wysiwyg.setData("<p>This is my reason</p>");');

$I->fillField(['name' => 'Initiator[primaryName]'], 'My Name');
$I->fillField(['name' => 'Initiator[contactEmail]'], 'test@example.org');
$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');
$I->click('#motionConfirmedForm .btn');

$I->see('Our second statutes', '.amendmentLink' . (AcceptanceTester::FIRST_FREE_AMENDMENT_ID + 1));
$I->see('S2', '.amendmentLink' . (AcceptanceTester::FIRST_FREE_AMENDMENT_ID + 1));
