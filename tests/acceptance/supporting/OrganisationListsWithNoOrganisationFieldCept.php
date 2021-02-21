<?php

/** @var \Codeception\Scenario $scenario */

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

/*
 * This tests an edge case where an organisation list is set, but the initiator field did not contain an organization.
 * If a person name was entered and the form is saved incompletely, the name should still be present
 * in the following form asking the fill out the missing information.
 */


$I->wantTo('Create three organisations');

$page = $I->loginAndGotoStdAdminPage()->gotoConsultation();

$I->executeJS('$("#organisationList").pillbox("addItems", -1, [{ "text": "Working group: environment" }]);');
$I->executeJS('$("#organisationList").pillbox("addItems", -1, [{ "text": "Working group: infrastructure" }]);');
$I->executeJS('$("#organisationList").pillbox("addItems", -1, [{ "text": "Working group: education" }]);');
$page->saveForm();
if ($I->executeJS('return $("#tagsList").pillbox("items").length') != 3) {
    $I->fail('Invalid return from tag-List');
}

$page = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->uncheckOption("//input[@name='initiatorCanBeOrganization']");
$I->uncheckOption("//input[@name='motionInitiatorSettings[hasOrganizations]']");
$page->saveForm();

$I->logout();

$I->apiSetUserFixedData('stdparteitag', 'std-parteitag', 'testuser@example.org', 'Test', 'User2', 'Orga', true);

$I->gotoConsultationHome();
$I->loginAsStdUser();

$page = $I->gotoMotionCreatePage();
$I->wait(1);

$I->seeInField('#initiatorPrimaryName', 'Test User2');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData("<p><strong>Test</strong></p>");');
$page->saveForm();

$I->wait(1);
$I->see('Keine Daten angegeben (Feld: Überschrift)');
$I->seeInField('#initiatorPrimaryName', 'Test User2');
