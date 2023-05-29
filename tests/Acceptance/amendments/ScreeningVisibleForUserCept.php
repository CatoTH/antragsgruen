<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('activate screening');
$I->gotoConsultationHome();
$I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->checkOption('#screeningAmendments');
$I->submitForm('#consultationSettingsForm', [], 'save');

$I->gotoConsultationHome();
$I->logout();
$I->loginAsStdUser();

$I->wantTo('create an amendment');

$page = $I->gotoConsultationHome()->gotoAmendmentCreatePage();
$page->createAmendment('Unscreened amendment', false);

$I->gotoConsultationHome();
$I->seeElement('.motionListStd');
$I->dontSeeElement('.motionListStd .amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->seeElement('.myAmendmentList .amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);

$I->wantTo('check that other users don\'t see it');

$I->logout();
$I->gotoConsultationHome();
$I->dontSeeElement('.myAmendmentList .amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->loginAsStdAdmin();
$I->gotoConsultationHome();
$I->dontSeeElement('.myAmendmentList .amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
