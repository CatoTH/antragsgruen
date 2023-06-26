<?php

/** @var \Codeception\Scenario $scenario */
use app\models\settings\Consultation;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);

$I->populateDBData1();

$I->wantTo('activate statute amendments and create two base statutes');
$I->loginAndGotoStdAdminPage();
$I->click('.motionTypeCreate a');

$I->checkOption('.presetStatute');
$I->fillField('#typeMotionPrefix', 'ST');
$I->fillField('#typeTitleSingular', 'Statute amendment');
$I->fillField('#typeTitlePlural', 'Statute amendments');
$I->fillField('#typeCreateTitle', 'Create a statute amendment');
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

$I->click('.statuteCreateLnk');
$I->fillField('#sections_' . AcceptanceTester::FIRST_FREE_MOTION_SECTION, 'Additional statute');
$sectionId = 'sections_' . (AcceptanceTester::FIRST_FREE_MOTION_SECTION + 1) . '_wysiwyg';
$I->executeJS('CKEDITOR.instances.' . $sectionId . '.setData("<p>Dummy text</p>");');
$I->submitForm('#motionEditForm', [], 'save');
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->click('.btnBack');

$I->see('Our statutes', '.baseStatutesList .statute' . AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->see('Additional statute', '.baseStatutesList .statute' . (AcceptanceTester::FIRST_FREE_MOTION_ID + 1));


$I->wantTo('create an agenda');
$page = $I->gotoStdAdminPage()->gotoAppearance();
$I->selectOption('#startLayoutType', Consultation::START_LAYOUT_AGENDA);
$page->saveForm();

$I->gotoConsultationHome();
$I->executeJS('$(".agendaItemAdder").last().find("a.addEntry").click()');
$I->executeJS('$(".agendaItemEditForm").last().find(".title input").val("Earth");');
$I->executeJS('$(".agendaItemEditForm").last().trigger("submit");');

$I->executeJS('$(".agendaItemAdder").last().find("a.addEntry").click()');
$I->executeJS('$(".agendaItemEditForm").last().find(".title input").val("Mars");');
$I->executeJS('$(".agendaItemEditForm").last().find(".motionType select").val(' . AcceptanceTester::FIRST_FREE_MOTION_TYPE . ');');
$I->executeJS('$(".agendaItemEditForm").last().trigger("submit");');
$I->wait(0.5);

$I->wantTo('test that creating the statute amendment works');
$I->see('Create a statute amendment', '#agendaitem_' . (AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID + 1) . ' .motionCreateLink');
$I->click('#agendaitem_' . (AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID + 1) . ' .motionCreateLink');
$I->see('Mars: Statute amendment', 'h1');
$I->click('.statute' . (AcceptanceTester::FIRST_FREE_MOTION_ID + 1) . ' a');
$I->wait(1);
$I->executeJS('CKEDITOR.instances.' . $sectionId . '.setData(\'<p>Set a new text</p>\');');
$I->executeJS('CKEDITOR.instances.amendmentReason_wysiwyg.setData(\'<p>Reason</p>\');');
$I->fillField(['name' => 'Initiator[primaryName]'], 'My Name');
$I->fillField(['name' => 'Initiator[contactEmail]'], 'test@example.org');
$I->submitForm('#amendmentEditForm', [], 'save');

$I->see('Set a new', 'ins');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');
$I->submitForm('#motionConfirmedForm', [], '');


$I->wantTo('See the new amendment being assigned to the agenda item');
$I->see('ST1', '#agendaitem_' . (AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID + 1) . ' .amendmentRow' . (AcceptanceTester::FIRST_FREE_AMENDMENT_ID));
$I->see('Additional statute', '#agendaitem_' . (AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID + 1) . ' .amendmentRow' . (AcceptanceTester::FIRST_FREE_AMENDMENT_ID));
$I->click('#agendaitem_' . (AcceptanceTester::FIRST_FREE_AGENDA_ITEM_ID + 1) . ' .amendmentRow' . (AcceptanceTester::FIRST_FREE_AMENDMENT_ID) . ' .title a');
$I->see('Set a new', 'ins');
