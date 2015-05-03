<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('switch to motion screening mode');
$I->gotoStdConsultationHome();
$I->loginAsStdAdmin();
$consultationSettingPage = $I->gotoStdAdminPage()->gotoConsultation();
$I->cantSeeCheckboxIsChecked('#screeningMotions');
$I->checkOption('#screeningMotions');
$consultationSettingPage->saveForm();
$I->canSeeCheckboxIsChecked('#screeningMotions');



$motionTitle = 'My new, screened motion';

$I->wantTo('create a motion as a logged out user');
$I->gotoStdConsultationHome();
$I->logout();

$page = $I->gotoStdConsultationHome()->gotoMotionCreatePage();
$page->fillInValidSampleData($motionTitle);
$page->saveForm();

$I->see(mb_strtoupper('Antrag bestätigen'), 'h1');
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->see(mb_strtoupper('Antrag eingereicht'), 'h1');

$I->wantTo('check that the motion is not visible yet');
$I->gotoStdConsultationHome();
$I->dontSee($motionTitle);


$I->wantTo('go to the admin page');
$I->gotoStdConsultationHome();
$I->loginAsStdAdmin();
$I->gotoStdAdminPage();

$I->see($motionTitle, '.adminTodo');


if (method_exists($I, 'executeJS')) {
    $I->wantTo('Screen the motion with an invalid title String (race condition)');
    $I->click('.adminTodo .motionScreen3 a');
    $I->seeElement('#motionScreenForm');
    $I->executeJS('$("#motionScreenForm input[name=titlePrefix]").attr("value", "A2");');
    $I->submitForm('#motionScreenForm', [], ['screen']);
    $I->see('Inzwischen gibt es einen anderen Antrag mit diesem Kürzel.');
}


$I->wantTo('screen the motion normally');
$I->seeElement('#motionScreenForm');
$I->submitForm('#motionScreenForm', [], ['screen']);
$I->see('Der Antrag wurde freigeschaltet.');


$I->wantTo('check if the motion is visible now');
$I->gotoStdConsultationHome();
$I->see($motionTitle, '.motionListStd');
$I->see($motionTitle, '#sidebar ul.motions');
