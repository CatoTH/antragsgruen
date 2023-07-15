<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome()->gotoMotionCreatePage();
$I->seeElement('#tagSelect');


$I->wantTo('disable tags for users');
$page = $I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->seeCheckboxIsChecked('#allowUsersToSetTags');
$I->uncheckOption('#allowUsersToSetTags');
$page->saveForm();

$I->gotoConsultationHome();
$I->logout();


$page = $I->gotoMotionCreatePage();
$I->dontSeeElement('#tagSelect');

$page->fillInValidSampleData();
$page->saveForm();
$I->submitForm('#motionConfirmForm', [], 'confirm');
