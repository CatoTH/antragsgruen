<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->seeElement('.feedMotions');
$I->seeElement('.feedAmendments');
$I->seeElement('.feedComments');
$I->seeElement('.feedAll');

$I->wantTo('deactivate the feeds');

$I->loginAndGotoStdAdminPage()->gotoConsultationExtended();
$I->seeCheckboxIsChecked('#showFeeds');
$I->uncheckOption('#showFeeds');
$I->submitForm('#consultationSettingsForm', [], 'save');

$I->gotoConsultationHome();
$I->dontSeeElement('.feedMotions');
$I->dontSeeElement('.feedAmendments');
$I->dontSeeElement('.feedComments');
$I->dontSeeElement('.feedAll');



$I->wantTo('activate the feeds again');
$I->gotoStdAdminPage()->gotoConsultationExtended();
$I->dontSeeCheckboxIsChecked('#showFeeds');
$I->checkOption('#showFeeds');
$I->submitForm('#consultationSettingsForm', [], 'save');

$I->gotoConsultationHome();
$I->seeElement('.feedMotions');
$I->seeElement('.feedAmendments');
$I->seeElement('.feedComments');
$I->seeElement('.feedAll');
