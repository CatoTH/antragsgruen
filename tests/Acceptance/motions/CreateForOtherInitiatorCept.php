<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('check that this option is not available for normal users');

$createPage = $I->gotoConsultationHome()->gotoMotionCreatePage();
$I->dontSeeElement('input[name=otherInitiator]');

$I->wantTo('check that it is available as an admin (and preselected)');
$I->loginAsStdAdmin();
$I->seeElement('input[name=otherInitiator]');
$I->seeCheckboxIsChecked('input[name=otherInitiator]');
$I->seeInField('#initiatorPrimaryName', '');
$I->seeInField('#initiatorEmail', '');


$I->wantTo('create a motion as another user');
$createPage->fillInValidSampleData('Testantrag 1');
$createPage->saveForm();
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->see(mb_strtoupper('Antrag veröffentlicht'), 'h1');
$I->submitForm('#motionConfirmedForm', [], '');
$I->see('Testantrag 1');
$I->gotoMotion(true, AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->see('Testantrag 1');
$I->dontSeeElement('.sidebarActions .withdraw');
$I->dontSeeElement('.sidebarActions .edit');



$I->wantTo('create a motion as myself');
$createPage = $I->gotoConsultationHome()->gotoMotionCreatePage();
$I->uncheckOption('input[name=otherInitiator]');
$createPage->fillInValidSampleData('Testantrag 2');
$createPage->saveForm();
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->see(mb_strtoupper('Antrag veröffentlicht'), 'h1');
$I->submitForm('#motionConfirmedForm', [], '');
$I->see('Testantrag 2');
$I->gotoMotion(true, AcceptanceTester::FIRST_FREE_MOTION_ID + 1);
$I->see('Testantrag 2');
$I->seeElement('.sidebarActions .withdraw');
$I->dontSeeElement('.sidebarActions .edit');



$I->wantTo('enable motion editing for initiators');
$I->gotoStdAdminPage()->gotoConsultation();
$I->checkOption('#iniatorsMayEdit');
$I->submitForm('#consultationSettingsForm', [], 'save');
$I->gotoMotion(true, AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->dontSeeElement('.sidebarActions .withdraw');
$I->dontSeeElement('.sidebarActions .edit');

$I->gotoMotion(true, AcceptanceTester::FIRST_FREE_MOTION_ID + 1);
$I->seeElement('.sidebarActions .withdraw');
$I->seeElement('.sidebarActions .edit');

$I->click('.sidebarActions .edit a');
$I->dontSeeCheckboxIsChecked('input[name=otherInitiator]');
