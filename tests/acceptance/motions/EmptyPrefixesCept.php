<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('remove all motion prefixes');

$I->loginAndGotoStdAdminPage()->gotoMotionList()->gotoMotionEdit(2);
$I->fillField('#motionTitlePrefix', '');
$I->submitForm('#motionUpdateForm', [], 'save');

$I->gotoStdAdminPage()->gotoMotionList()->gotoMotionEdit(3);
$I->fillField('#motionTitlePrefix', '');
$I->submitForm('#motionUpdateForm', [], 'save');

$I->gotoStdAdminPage()->gotoMotionList()->gotoMotionEdit(58);
$I->fillField('#motionTitlePrefix', '');
$I->submitForm('#motionUpdateForm', [], 'save');


$I->wantTo('check that all motions are still visible');

$I->gotoConsultationHome();
$I->seeElement('.motionLink2');
$I->seeElement('.motionLink3');
$I->seeElement('.motionLink58');
