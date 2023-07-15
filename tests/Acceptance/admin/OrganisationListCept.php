<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


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


$I->wantTo('see the organisations when creating motions');

$I->gotoConsultationHome()->gotoMotionCreatePage();
$I->fillField(['name' => 'sections[1]'], 'Testing motion');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData("<p><strong>Test</strong></p>");');
$I->executeJS('CKEDITOR.instances.sections_3_wysiwyg.setData("<p><strong>Test 2</strong></p>");');

$I->seeElement('#initiatorPrimaryName');
$I->dontSeeElement('#initiatorPrimaryOrgaName');
$I->seeElementInDOM('#initiatorPrimaryOrgaName');
$I->seeElement('#initiatorOrga');

$I->fillField('#initiatorPrimaryName', 'Tester');
$I->executeJS('$("#initiatorOrga").val("Working group: infrastructure");');
$I->fillField('#initiatorEmail', 'tobias@hoessl.eu');
$I->submitForm('#motionEditForm', [], 'save');

$I->see('Tester (Working group: infrastructure)');

$I->submitForm('#motionConfirmForm', [], 'modify');
$selectedOrga = $I->executeJS('return $("#initiatorOrga").val()');
$I->assertEquals('Working group: infrastructure', $selectedOrga);

$I->executeJS('$("#personTypeOrga").prop("checked", true).trigger("change")');

$I->dontSeeElement('#initiatorPrimaryName');
$I->seeElement('#initiatorPrimaryOrgaName');
$I->dontSeeElement('#initiatorOrga');

$I->executeJS('$("#initiatorPrimaryOrgaName").val("Working group: infrastructure").trigger("change");');
$I->fillField('#resolutionDate', '07.12.2019');

$I->submitForm('#motionEditForm', [], 'save');

$I->see('Working group: infrastructure (dort beschlossen am: 07.12.2019)');

$I->submitForm('#motionConfirmForm', [], 'modify');

$I->dontSeeElement('#initiatorPrimaryName');
$I->seeElement('#initiatorPrimaryOrgaName');
$I->dontSeeElement('#initiatorOrga');
$selectedOrga = $I->executeJS('return $("#initiatorPrimaryOrgaName").val()');
$I->assertEquals('Working group: infrastructure', $selectedOrga);


$I->wantTo('test the same for amendments');

$I->gotoConsultationHome()->gotoAmendmentCreatePage();

$I->seeElement('#initiatorPrimaryName');
$I->dontSeeElement('#initiatorPrimaryOrgaName');
$I->seeElementInDOM('#initiatorPrimaryOrgaName');
$I->seeElement('#initiatorOrga');

$I->fillField('#initiatorPrimaryName', 'Tester');
$I->executeJS('$("#initiatorOrga").val("Working group: infrastructure");');
$I->fillField('#initiatorEmail', 'tobias@hoessl.eu');
$I->submitForm('#amendmentEditForm', [], 'save');

$I->see('Tester (Working group: infrastructure)');

$I->submitForm('#amendmentConfirmForm', [], 'modify');
$selectedOrga = $I->executeJS('return $("#initiatorOrga").val()');
$I->assertEquals('Working group: infrastructure', $selectedOrga);

$I->executeJS('$("#personTypeOrga").prop("checked", true).trigger("change")');

$I->dontSeeElement('#initiatorPrimaryName');
$I->seeElement('#initiatorPrimaryOrgaName');
$I->dontSeeElement('#initiatorOrga');

$I->executeJS('$("#initiatorPrimaryOrgaName").val("Working group: infrastructure").trigger("change");');
$I->fillField('#resolutionDate', '07.12.2019');

$I->submitForm('#amendmentEditForm', [], 'save');

$I->see('Working group: infrastructure (dort beschlossen am: 07.12.2019)');

$I->submitForm('#amendmentConfirmForm', [], 'modify');

$I->dontSeeElement('#initiatorPrimaryName');
$I->seeElement('#initiatorPrimaryOrgaName');
$I->dontSeeElement('#initiatorOrga');
$selectedOrga = $I->executeJS('return $("#initiatorPrimaryOrgaName").val()');
$I->assertEquals('Working group: infrastructure', $selectedOrga);
