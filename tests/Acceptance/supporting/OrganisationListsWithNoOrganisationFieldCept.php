<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

/*
 * This tests an edge case where an organisation list is set, but the initiator field did not contain an organisation.
 * If a person name was entered and the form is saved incompletely, the name should still be present
 * in the following form asking the fill out the missing information.
 */


$I->wantTo('Create three organisations');

$I->loginAndGotoStdAdminPage()->gotoUserAdministration();

$I->dontSeeElement('.editOrganisationModal');
$I->clickJS('.orgaOpenerHolder .orgaOpener');
$I->wait(0.5);
$I->seeElement('.editOrganisationModal');
$I->clickJS('.editOrganisationModal .btnAdd');
$I->clickJS('.editOrganisationModal .btnAdd');
$I->clickJS('.editOrganisationModal .btnAdd');

$I->executeJS('document.querySelectorAll(".editOrganisationModal input.form-control").item(0).value = "Working group: environment"');
$I->executeJS('document.querySelectorAll(".editOrganisationModal input.form-control").item(1).value = "Working group: infrastructure"');
$I->executeJS('document.querySelectorAll(".editOrganisationModal input.form-control").item(2).value = "Working group: education"');

$I->clickJS('.editOrganisationModal .btnSave');

$I->clickJS('.orgaOpenerHolder .orgaOpener');
$I->wait(0.5);
$I->seeInField('.editOrganisationModal input', 'Working group: environment');
$I->seeInField('.editOrganisationModal input', 'Working group: infrastructure');
$I->seeInField('.editOrganisationModal input', 'Working group: education');

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
