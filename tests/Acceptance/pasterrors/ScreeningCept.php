<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('activate screening');

$I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->checkOption('#screeningMotions');
$I->checkOption('#screeningAmendments');
$I->submitForm('#consultationSettingsForm', [], 'save');



$I->wantTo('check that prefixes are set on amendments when screening using the motion-list');

$page = $I->gotoConsultationHome()->gotoAmendmentCreatePage(2);

$I->executeJS('window.newText = CKEDITOR.instances.sections_2_wysiwyg.getData();');
$I->executeJS('window.newText = window.newText.replace(/woschechta Bayer/g, "Sauprei&szlig;");');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(window.newText);');
$I->executeJS('CKEDITOR.instances.amendmentReason_wysiwyg.setData("<p>This is my reason</p>");');
$page->fillInValidSampleData('Neuer Testantrag');
$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');

$I->gotoMotionList();
$I->dontSee(AcceptanceTester::FIRST_FREE_AMENDMENT_TITLE_PREFIX);
$I->checkOption('.adminMotionTable .amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID . ' .selectbox');
$I->submitForm('.motionListForm', [], 'screen');
$I->see('Die ausgewählten Anträge wurden freigeschaltet.');
$I->see(AcceptanceTester::FIRST_FREE_AMENDMENT_TITLE_PREFIX);



$I->wantTo('check that prefixes are set on motions when screening using the motion-list');
$I->gotoConsultationHome()->gotoMotionCreatePage()->fillInValidSampleData();
$I->submitForm('#motionEditForm', [], 'save');
$I->submitForm('#motionConfirmForm', [], 'confirm');

$I->gotoMotionList();
$I->dontSee(AcceptanceTester::FIRST_FREE_MOTION_TITLE_PREFIX);
$I->checkOption('.adminMotionTable .motion' . AcceptanceTester::FIRST_FREE_MOTION_ID . ' .selectbox');
$I->submitForm('.motionListForm', [], 'screen');
$I->see('Die ausgewählten Anträge wurden freigeschaltet.');
$I->see(AcceptanceTester::FIRST_FREE_MOTION_TITLE_PREFIX);
