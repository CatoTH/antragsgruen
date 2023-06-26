<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('enable merging for users in restricted mode');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->seeCheckboxIsChecked('#initiatorsCanMerge0');
$I->checkOption('#initiatorsCanMerge1');
$I->submitForm('.adminTypeForm', [], 'save');
$I->seeCheckboxIsChecked('#initiatorsCanMerge1');


$I->wantTo('ensure I see the simplified version of the form');
$I->gotoConsultationHome()->gotoMotionView(58);
$I->logout();
$I->loginAsStdUser();

$I->click('.sidebarActions .mergeamendments');
$I->dontSeeElement('.motionMergeInit');
$I->seeElement('.motionMergeForm');
$I->dontSeeElement('.motionData .alert-info');
$I->dontSeeElement('.newAmendments');
$I->dontSeeElement('.mergeActionHolder');
