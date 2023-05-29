<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('activate screening');
$I->gotoConsultationHome();
$I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->checkOption('#screeningMotions');
$I->submitForm('#consultationSettingsForm', [], 'save');

$I->gotoConsultationHome();
$I->logout();
$I->loginAsStdUser();

$I->wantTo('create a motion');

$page = $I->gotoConsultationHome()->gotoMotionCreatePage();
$page->createMotion('Unscreened motion', true);
$I->gotoConsultationHome();

$I->seeElement('.motionListStd');
$I->dontSee('Unscreened motion', '.motionListStd');
$I->see('Unscreened motion', '.myMotionList');

$I->wantTo('check that other users don\'t see it');

$I->logout();
$I->gotoConsultationHome();
$I->dontSee('Unscreened motion', '.myMotionList');
$I->loginAsStdAdmin();
$I->gotoConsultationHome();
$I->dontSee('Unscreened motion', '.myMotionList');
