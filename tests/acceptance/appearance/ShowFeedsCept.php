<?php

/** @var \Codeception\Scenario $scenario */

use app\models\policies\IPolicy;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->click('#sidebar .feeds a');
$I->seeElement('.feedMotions');
$I->seeElement('.feedAmendments');
$I->seeElement('.feedComments');
$I->seeElement('.feedAll');


$I->wantTo('deactivate some feeds');

$I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->selectFueluxOption('#typePolicyMotions', IPolicy::POLICY_NOBODY);
$I->selectFueluxOption('#typePolicyAmendments', IPolicy::POLICY_NOBODY);
$I->selectFueluxOption('#typePolicyComments', IPolicy::POLICY_NOBODY);
$I->submitForm('.adminTypeForm', [], 'save');


$I->gotoConsultationHome();
$I->click('#sidebar .feeds a');
$I->dontSeeElement('.feedMotions');
$I->dontSeeElement('.feedAmendments');
$I->dontSeeElement('.feedComments');
$I->dontSeeElement('.feedAll');



$I->wantTo('activate the feeds again');

$I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->selectFueluxOption('#typePolicyMotions', IPolicy::POLICY_ALL);
$I->selectFueluxOption('#typePolicyAmendments', IPolicy::POLICY_ALL);
$I->selectFueluxOption('#typePolicyComments', IPolicy::POLICY_ALL);
$I->submitForm('.adminTypeForm', [], 'save');

$I->gotoConsultationHome();
$I->click('#sidebar .feeds a');
$I->seeElement('.feedMotions');
$I->seeElement('.feedAmendments');
$I->seeElement('.feedComments');
$I->seeElement('.feedAll');
