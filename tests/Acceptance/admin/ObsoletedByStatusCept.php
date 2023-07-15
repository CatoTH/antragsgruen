<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('set a motion to be obsoleted by another motion');
$page = $I->loginAndGotoMotionList()->gotoMotionEdit(2);
$I->seeElement('#motionStatusString');
$I->dontSeeElement('#motionStatusMotion');
$I->selectOption('#motionStatus', \app\models\db\IMotion::STATUS_OBSOLETED_BY_MOTION);
$I->dontSeeElement('#motionStatusString');
$I->seeElement('#motionStatusMotion');
$I->selectOption('#motionStatusMotion', 3);
$page->saveForm();

$I->seeElement('#motionStatusMotion');
$I->seeOptionIsSelected('#motionStatusMotion', 'A3');
$I->click('#sidebar .view');
$I->see('A3', '.motionDataTable .statusRow a');

$I->wantTo('set a motion to be obsoleted by another amendment');
$page = $I->gotoMotionList()->gotoMotionEdit(2);
$I->dontSeeElement('#motionStatusAmendment');
$I->selectOption('#motionStatus', \app\models\db\IMotion::STATUS_OBSOLETED_BY_AMENDMENT);
$I->dontSeeElement('#motionStatusString');
$I->dontSeeElement('#motionStatusMotion');
$I->seeElement('#motionStatusAmendment');
$I->selectOption('#motionStatusAmendment', 279);
$page->saveForm();

$I->seeElement('#motionStatusAmendment');
$I->seeOptionIsSelected('#motionStatusAmendment', 'Ä1 zu A8');
$I->click('#sidebar .view');
$I->see('Ä1 zu A8', '.motionDataTable .statusRow a');


$I->wantTo('set an amendment to be obsoleted by another motion');
$page = $I->gotoMotionList()->gotoAmendmentEdit(1);
$I->seeElement('#amendmentStatusString');
$I->dontSeeElement('#amendmentStatusMotion');
$I->selectOption('#amendmentStatus', \app\models\db\IMotion::STATUS_OBSOLETED_BY_MOTION);
$I->dontSeeElement('#amendmentStatusString');
$I->seeElement('#amendmentStatusMotion');
$I->selectOption('#amendmentStatusMotion', 3);
$page->saveForm();

$I->seeElement('#amendmentStatusMotion');
$I->seeOptionIsSelected('#amendmentStatusMotion', 'A3');
$I->click('#sidebar .view');
$I->see('A3', '.motionDataTable .statusRow a');

$I->wantTo('set an amendment to be obsoleted by another amendment');
$page = $I->gotoMotionList()->gotoAmendmentEdit(1);
$I->dontSeeElement('#amendmentStatusAmendment');
$I->selectOption('#amendmentStatus', \app\models\db\IMotion::STATUS_OBSOLETED_BY_AMENDMENT);
$I->dontSeeElement('#amendmentStatusString');
$I->dontSeeElement('#amendmentStatusMotion');
$I->seeElement('#amendmentStatusAmendment');
$I->selectOption('#amendmentStatusAmendment', 279);
$page->saveForm();

$I->seeElement('#amendmentStatusAmendment');
$I->seeOptionIsSelected('#amendmentStatusAmendment', 'Ä1 zu A8');
$I->click('#sidebar .view');
$I->see('Ä1 zu A8', '.motionDataTable .statusRow a');


