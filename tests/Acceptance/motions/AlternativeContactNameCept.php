<?php

/** @var \Codeception\Scenario $scenario */
use app\models\settings\InitiatorForm;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('motion Create site loads');
$I->gotoConsultationHome()->gotoMotionCreatePage();
$I->dontSeeElement('#initiatorContactName');

$form = $I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->seeOptionIsSelected("input[name=\"motionInitiatorSettings[contactName]\"]", (string)InitiatorForm::CONTACT_NONE);
$I->selectOption("input[name=\"motionInitiatorSettings[contactName]\"]", (string)InitiatorForm::CONTACT_REQUIRED);
$form->saveForm();
$I->seeOptionIsSelected("input[name=\"motionInitiatorSettings[contactName]\"]", (string)InitiatorForm::CONTACT_REQUIRED);

$form = $I->gotoConsultationHome()->gotoMotionCreatePage();
$I->wait(1);
$I->seeElement('#initiatorContactName');
$I->assertEquals(true, $I->executeJS('return $("#initiatorContactName").prop("required")'));
$form->fillInValidSampleData();
$I->fillField('#initiatorContactName', 'Alternative contact person');
$form->saveForm();

$I->submitForm('#motionConfirmForm', [], 'modify');
$I->seeInField('#initiatorContactName', 'Alternative contact person');
