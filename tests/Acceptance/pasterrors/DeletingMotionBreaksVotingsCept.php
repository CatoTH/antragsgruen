<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('Delete a motion');

$I->loginAndGotoMotionList()->gotoMotionEdit(2);

$I->click('.motionDeleteForm button');
$I->wait(1);
$I->acceptBootboxConfirm();
$I->see('Der Antrag wurde gelöscht.');

// This checks that the page is not completely broken
$I->gotoStdAdminPage()->gotoVotingPage();
$I->see('eine Einführung und Anleitung');
