<?php

/** @var \Codeception\Scenario $scenario */

use app\models\db\EMailLog;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('activate confirmation e-mails');
$I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->checkOption('#screeningMotions');
$I->checkOption('#screeningAmendments');
$I->checkOption('#initiatorConfirmEmails');
$I->submitForm('#consultationSettingsForm', [], 'save');


$I->wantTo('create a motion as user');

$I->gotoConsultationHome()->gotoMotionCreatePage()->fillInValidSampleData('Testantrag1');
$I->uncheckOption("//input[@name='otherInitiator']");
$I->submitForm('#motionEditForm', [], 'save');
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->see('E-Mail sent to: testadmin@example.org (Type ' . EMailLog::TYPE_MOTION_NOTIFICATION_ADMIN . ')');
$I->see('E-Mail sent to: test@example.org (Type ' . EMailLog::TYPE_MOTION_SUBMIT_CONFIRM . ')');


$I->wantTo('create a motion as admin');

$I->gotoConsultationHome()->gotoMotionCreatePage()->fillInValidSampleData('Testantrag1');
$I->seeCheckboxIsChecked("//input[@name='otherInitiator']");
$I->submitForm('#motionEditForm', [], 'save');
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->see('E-Mail sent to: testadmin@example.org (Type ' . EMailLog::TYPE_MOTION_NOTIFICATION_ADMIN . ')');
$I->dontSee('E-Mail sent to: test@example.org');


$I->wantTo('create an amendment as user');

$I->gotoConsultationHome()->gotoAmendmentCreatePage();
$I->uncheckOption("//input[@name='otherInitiator']");
$I->wait(1);
$I->fillField('#sections_1', 'New title');
$I->fillField('#initiatorPrimaryName', 'My Name');
$I->fillField('#initiatorEmail', 'test@example.org');
$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');
$I->see('E-Mail sent to: testadmin@example.org (Type ' . EMailLog::TYPE_MOTION_NOTIFICATION_ADMIN . ')');
$I->see('E-Mail sent to: test@example.org (Type ' . EMailLog::TYPE_MOTION_SUBMIT_CONFIRM . ')');


$I->wantTo('create an amendment as admin');

$I->gotoConsultationHome()->gotoAmendmentCreatePage();
$I->seeCheckboxIsChecked("//input[@name='otherInitiator']");
$I->wait(1);
$I->fillField('#sections_1', 'New title');
$I->fillField('#initiatorPrimaryName', 'My Name');
$I->fillField('#initiatorEmail', 'test@example.org');
$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');
$I->see('E-Mail sent to: testadmin@example.org (Type ' . EMailLog::TYPE_MOTION_NOTIFICATION_ADMIN . ')');
$I->dontSee('E-Mail sent to: test@example.org');


$I->wantTo('screen the motion / amendment');
$I->gotoMotionList()->gotoMotionEdit(AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->submitForm('#motionScreenForm', [], 'screen');
$I->see('E-Mail sent to: test@example.org (Type ' . EMailLog::TYPE_MOTION_SUBMIT_CONFIRM . ')');

$I->gotoMotionList()->gotoAmendmentEdit(AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->submitForm('#amendmentScreenForm', [], 'screen');
$I->see('E-Mail sent to: test@example.org (Type ' . EMailLog::TYPE_MOTION_SUBMIT_CONFIRM . ')');
