<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('delete a motion');
$page = $I->loginAndGotoMotionList();
$I->see('A2');
$I->see('A3');
$I->seeElement('.amendment3');
$page->gotoMotionEdit(3);
$I->wait(1);

$I->click('.motionDeleteForm button');
$I->wait(1);
$I->acceptBootboxConfirm();
$I->see('Der Antrag wurde gelöscht.');
$I->see('A2');
$I->dontSee('A3');
$I->seeElement('.amendment3');

$I->wantTo('delete an amendment');
$page->gotoAmendmentEdit(3);
$I->wait(1);
$I->click('.amendmentDeleteForm button');
$I->wait(1);
$I->acceptBootboxConfirm();
$I->see('Der Änderungsantrag wurde gelöscht.');
$I->see('A2');
$I->dontSee('A3');
$I->dontSeeElement('.amendment3');
