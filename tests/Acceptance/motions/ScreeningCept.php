<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('switch to motion screening mode');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->dontSeeElement('#adminTodo');
$consultationSettingPage = $I->gotoStdAdminPage()->gotoConsultation();
$I->cantSeeCheckboxIsChecked('#screeningMotions');
$I->checkOption('#screeningMotions');
$consultationSettingPage->saveForm();
$I->canSeeCheckboxIsChecked('#screeningMotions');


$motionTitle = 'My new, screened motion';

$I->wantTo('create a motion as a logged out user');
$I->gotoConsultationHome();
$I->logout();

$page = $I->gotoConsultationHome()->gotoMotionCreatePage();
$page->createMotion($motionTitle, true);
$I->see('Er wird nun auf formale Richtigkeit geprüft und dann freigeschaltet.');

$I->wantTo('check that the motion is not visible yet');
$I->gotoConsultationHome();
$I->dontSee($motionTitle);


$I->wantTo('go to the admin page');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->click('#adminTodo');

$I->see($motionTitle, '.adminTodo');


$I->wantTo('Screen the motion with an invalid title String (race condition)');
$I->click('.adminTodo .motionScreen' . AcceptanceTester::FIRST_FREE_MOTION_ID . ' a');
$I->seeElement('#motionScreenForm');
$prefix = AcceptanceTester::FIRST_FREE_MOTION_TITLE_PREFIX;
$I->executeJS('$("#motionScreenForm input[name=titlePrefix]").attr("value", "A3");');
$I->submitForm('#motionScreenForm', [], 'screen');
$I->see('Das angegebene Antragskürzel wird bereits von einem anderen Antrag verwendet.');


$I->wantTo('screen the motion normally');
$I->seeElement('#motionScreenForm');
$I->submitForm('#motionScreenForm', [], 'screen');
$I->see('Der Antrag wurde freigeschaltet.');


$I->wantTo('check if the motion is visible now');
$I->gotoConsultationHome();
$I->see($motionTitle, '.motionListStd');
$I->see($motionTitle, '#sidebar ul.motions');
