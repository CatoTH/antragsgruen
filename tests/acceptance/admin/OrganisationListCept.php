<?php

/** @var \Codeception\Scenario $scenario */

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('Create three organisations');

$page = $I->loginAndGotoStdAdminPage()->gotoConsultation();

$I->executeJS('$("#organisationList").pillbox("addItems", -1, [{ "text": "Working group: environment" }]);');
$I->executeJS('$("#organisationList").pillbox("addItems", -1, [{ "text": "Working group: infrastructure" }]);');
$I->executeJS('$("#organisationList").pillbox("addItems", -1, [{ "text": "Working group: education" }]);');
if ($I->executeJS('return $("#tagsList").pillbox("items").length') != 3) {
    $I->fail('Invalid return from tag-List');
}

$page->saveForm();

if ($I->executeJS('return $("#tagsList").pillbox("items").length') != 3) {
    $I->fail('Invalid return from tag-List');
}


$I->wantTo('see the organisations when creating motions');

$I->gotoConsultationHome()->gotoMotionCreatePage();
$I->fillField(['name' => 'sections[1]'], 'Testing motion');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData("<p><strong>Test</strong></p>");');
$I->executeJS('CKEDITOR.instances.sections_3_wysiwyg.setData("<p><strong>Test 2</strong></p>");');

$I->seeElement('#initiatorPrimaryName');
$I->dontSeeElement('#initiatorPrimaryOrgaName');
$I->seeElementInDOM('#initiatorPrimaryOrgaName');
$I->seeElement('#initiatorOrga.selectlist');

$I->fillField('#initiatorPrimaryName', 'Tester');
$I->executeJS('$("#initiatorOrga").selectlist("selectByValue", "Working group: infrastructure");');
$I->fillField('#initiatorEmail', 'tobias@hoessl.eu');
$I->submitForm('#motionEditForm', [], 'save');

$I->see('Tester (Working group: infrastructure)');

$I->submitForm('#motionConfirmForm', [], 'modify');
$selectedOrga = $I->executeJS('return $("#initiatorOrga").selectlist("getValue").value');
$I->assertEquals('Working group: infrastructure', $selectedOrga);

$I->executeJS('$("#personTypeOrga").prop("checked", true).trigger("change")');

$I->dontSeeElement('#initiatorPrimaryName');
$I->seeElement('#initiatorPrimaryOrgaName');
$I->dontSeeElement('#initiatorOrga.selectlist');

$I->executeJS('$("#initiatorPrimaryOrgaName").selectlist("selectByValue", "Working group: infrastructure").trigger("changed.fu.selectlist");');
$I->fillField('#resolutionDate', '07.12.2019');

$I->submitForm('#motionEditForm', [], 'save');

$I->see('Working group: infrastructure (beschlossen am: 07.12.2019)');

$I->submitForm('#motionConfirmForm', [], 'modify');

$I->dontSeeElement('#initiatorPrimaryName');
$I->seeElement('#initiatorPrimaryOrgaName');
$I->dontSeeElement('#initiatorOrga.selectlist');
$selectedOrga = $I->executeJS('return $("#initiatorPrimaryOrgaName").selectlist("getValue").value');
$I->assertEquals('Working group: infrastructure', $selectedOrga);


$I->wantTo('test the same for amendments');

$I->gotoConsultationHome()->gotoAmendmentCreatePage();

$I->seeElement('#initiatorPrimaryName');
$I->dontSeeElement('#initiatorPrimaryOrgaName');
$I->seeElementInDOM('#initiatorPrimaryOrgaName');
$I->seeElement('#initiatorOrga.selectlist');

$I->fillField('#initiatorPrimaryName', 'Tester');
$I->executeJS('$("#initiatorOrga").selectlist("selectByValue", "Working group: infrastructure");');
$I->fillField('#initiatorEmail', 'tobias@hoessl.eu');
$I->submitForm('#amendmentEditForm', [], 'save');

$I->see('Tester (Working group: infrastructure)');

$I->submitForm('#amendmentConfirmForm', [], 'modify');
$selectedOrga = $I->executeJS('return $("#initiatorOrga").selectlist("getValue").value');
$I->assertEquals('Working group: infrastructure', $selectedOrga);

$I->executeJS('$("#personTypeOrga").prop("checked", true).trigger("change")');

$I->dontSeeElement('#initiatorPrimaryName');
$I->seeElement('#initiatorPrimaryOrgaName');
$I->dontSeeElement('#initiatorOrga.selectlist');

$I->executeJS('$("#initiatorPrimaryOrgaName").selectlist("selectByValue", "Working group: infrastructure").trigger("changed.fu.selectlist");');
$I->fillField('#resolutionDate', '07.12.2019');

$I->submitForm('#amendmentEditForm', [], 'save');

$I->see('Working group: infrastructure (beschlossen am: 07.12.2019)');

$I->submitForm('#amendmentConfirmForm', [], 'modify');

$I->dontSeeElement('#initiatorPrimaryName');
$I->seeElement('#initiatorPrimaryOrgaName');
$I->dontSeeElement('#initiatorOrga.selectlist');
$selectedOrga = $I->executeJS('return $("#initiatorPrimaryOrgaName").selectlist("getValue").value');
$I->assertEquals('Working group: infrastructure', $selectedOrga);


