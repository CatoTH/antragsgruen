<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->gotoConsultationHome();
$I->loginAsStdAdmin();

$I->wantTo('see no gender selection at first');
$I->gotoConsultationHome()->gotoMotionCreatePage();

$I->seeCheckboxIsChecked("#personTypeNatural");
$I->dontSeeElement('.genderRow');

$I->wantTo('set the gender as required');
$page = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->seeCheckboxIsChecked("//input[@name='motionInitiatorSettings[contactGender]'][@value='0']"); // None
$I->checkOption("//input[@name='motionInitiatorSettings[contactGender]'][@value='2']"); // Required
$page->saveForm();



$I->wantTo('see the field being required');
$page = $I->gotoConsultationHome()->gotoMotionCreatePage();
$I->seeElement('.genderRow');
$I->seeOptionIsSelected('#initiatorGender', '');

$page->fillInValidSampleData();
$I->click('#motionEditForm button[name=save]');

$I->seeBootboxDialog('Bitte gib etwas im Gender-Feld an');
$I->acceptBootboxAlert();

$I->wantTo('save the form');
$I->selectOption('#initiatorGender', 'diverse');
$I->seeOptionIsSelected('#initiatorGender', 'Inter/Divers');

$I->click('#motionEditForm button[name=save]');
$I->seeElement('#motionConfirmForm');


$I->wantTo('make a change');
$I->click('#motionConfirmForm button[name=modify]');
$I->seeOptionIsSelected('#initiatorGender', 'Inter/Divers');

$I->selectOption('#initiatorGender', 'female');
$I->click('#motionEditForm button[name=save]');
$I->seeElement('#motionConfirmForm');


$I->wantTo('make the selection optional');
$page = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->seeCheckboxIsChecked("//input[@name='motionInitiatorSettings[contactGender]'][@value='2']"); // Required
$I->checkOption("//input[@name='motionInitiatorSettings[contactGender]'][@value='1']"); // Optional
$page->saveForm();


$I->wantTo('see the field being optional');
$page = $I->gotoConsultationHome()->gotoMotionCreatePage();
$I->seeElement('.genderRow');
$I->seeOptionIsSelected('#initiatorGender', '');

$page->fillInValidSampleData();
$I->click('#motionEditForm button[name=save]');
$I->seeElement('#motionConfirmForm');
