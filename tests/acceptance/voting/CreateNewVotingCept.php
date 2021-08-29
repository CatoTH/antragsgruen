<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('enable non-quota speech lists');
$I->gotoConsultationHome();
$I->dontSeeElement('.currentSpeechInline');
$I->dontSeeElement('#speechAdminLink');

$I->loginAndGotoMotionList()->gotoAmendmentEdit(2);
// @TODO
