<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('test having only organizations enabled');
$I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->seeCheckboxIsChecked("//input[@name='initiatorCanBePerson']");
$I->seeCheckboxIsChecked("//input[@name='initiatorCanBeOrganization']");
$I->seeElement('.formGroupResolutionDate');
$I->seeElement('.formGroupGender');
$I->uncheckOption("//input[@name='initiatorCanBePerson']");
$I->seeElement('.formGroupResolutionDate');
$I->dontSeeElement('.formGroupGender');
$I->submitForm('.adminTypeForm', [], 'save');

$I->dontSeeCheckboxIsChecked("//input[@name='initiatorCanBePerson']");
$I->seeCheckboxIsChecked("//input[@name='initiatorCanBeOrganization']");


$I->logout();
$page = $I->gotoConsultationHome()->gotoMotionCreatePage();
$I->dontSeeElement('.personTypeSelector');
$I->dontSeeElementInDOM('#initiatorOrga');
$I->seeElement('#resolutionDate');
$page->fillInValidSampleData('Orga-Test');
$I->fillField('#resolutionDate', '09.09.1999');
$I->submitForm('#motionEditForm', [], 'save');

$I->seeElement('#motionConfirmForm');
$I->see('09.09.1999', '.motionTextHolder');


$I->wantTo('test having only natural persons enabled');
$I->gotoConsultationHome();
$I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->dontSeeCheckboxIsChecked("//input[@name='initiatorCanBePerson']");
$I->uncheckOption("//input[@name='initiatorCanBeOrganization']");
$I->seeCheckboxIsChecked("//input[@name='initiatorCanBePerson']");
$I->dontSeeElement('.formGroupResolutionDate');
$I->seeElement('.formGroupGender');
$I->submitForm('.adminTypeForm', [], 'save');


$I->logout();
$page = $I->gotoConsultationHome()->gotoMotionCreatePage();
$I->dontSeeElement('.personTypeSelector');
$I->seeElement('#initiatorOrga');
$I->dontSeeElement('#resolutionDate');
$page->fillInValidSampleData('Person-Test');
$I->submitForm('#motionEditForm', [], 'save');

$I->seeElement('#motionConfirmForm');
$I->see('Mein Name', '.motionTextHolder');
