<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('switch on screening and create two motions and amendments');

$I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->checkOption('#screeningMotions');
$I->checkOption('#screeningAmendments');
$I->submitForm('#consultationSettingsForm', [], 'save');

$page = $I->gotoConsultationHome()->gotoAmendmentCreatePage(2);
$I->executeJS('window.newText = CKEDITOR.instances.sections_2_wysiwyg.getData();');
$I->executeJS('window.newText = window.newText.replace(/woschechta Bayer/g, "Sauprei&szlig;");');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(window.newText);');
$I->executeJS('CKEDITOR.instances.amendmentReason_wysiwyg.setData("<p>This is my reason</p>");');
$page->fillInValidSampleData('Neuer Testantrag 1');
$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');

$page = $I->gotoConsultationHome()->gotoAmendmentCreatePage(2);
$I->executeJS('window.newText = CKEDITOR.instances.sections_2_wysiwyg.getData();');
$I->executeJS('window.newText = window.newText.replace(/woschechta Bayer/g, "Sauprei&szlig;");');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(window.newText);');
$I->executeJS('CKEDITOR.instances.amendmentReason_wysiwyg.setData("<p>This is my reason</p>");');
$page->fillInValidSampleData('Neuer Testantrag 2');
$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');

$I->gotoConsultationHome()->gotoMotionCreatePage()->fillInValidSampleData('Testantrag 1');
$I->submitForm('#motionEditForm', [], 'save');
$I->submitForm('#motionConfirmForm', [], 'confirm');

$I->gotoConsultationHome()->gotoMotionCreatePage()->fillInValidSampleData('Testantrag 2');
$I->submitForm('#motionEditForm', [], 'save');
$I->submitForm('#motionConfirmForm', [], 'confirm');


$I->wantTo('screen two of them; editing is still possible');

$I->gotoMotionList()->gotoAmendmentEdit(AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->seeElement('#amendmentTextEditCaller');
$I->submitForm('#amendmentScreenForm', [], 'screen');
$I->seeElement('#amendmentTextEditCaller');

$I->gotoMotionList()->gotoMotionEdit(AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->seeElement('#motionTextEditCaller');
$I->submitForm('#motionScreenForm', [], 'screen');
$I->seeElement('#motionTextEditCaller');



$I->wantTo('disabled editing published text');

$I->gotoStdAdminPage()->gotoConsultation();
$I->seeElement('#iniatorsMayEdit');
$I->uncheckOption('#adminsMayEdit');
$I->seeBootboxDialog('wirkt sich das auch auf alle bisherigen Anträge aus');
$I->acceptBootboxConfirm();
$I->dontSeeElement('#iniatorsMayEdit');
$I->submitForm('#consultationSettingsForm', [], 'save');



$I->wantTo('check that published motions are not editable anymore');

$I->gotoMotionList()->gotoAmendmentEdit(AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->dontSeeElement('#amendmentTextEditCaller');
$I->gotoMotionList()->gotoAmendmentEdit(AcceptanceTester::FIRST_FREE_AMENDMENT_ID + 1);
$I->seeElement('#amendmentTextEditCaller');
$I->submitForm('#amendmentScreenForm', [], 'screen');
$I->dontSeeElement('#amendmentTextEditCaller');

$I->gotoMotionList()->gotoMotionEdit(AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->dontSeeElement('#motionTextEditCaller');
$I->gotoMotionList()->gotoMotionEdit(AcceptanceTester::FIRST_FREE_MOTION_ID + 1);
$I->seeElement('#motionTextEditCaller');
$I->submitForm('#motionScreenForm', [], 'screen');
$I->dontSeeElement('#motionTextEditCaller');
