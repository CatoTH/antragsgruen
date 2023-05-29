<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('enter a voting result for a motion');

$I->loginAndGotoMotionList()->gotoMotionEdit(2);
$I->wait(0.1);

$I->dontSeeElement('.votingDataHolder');
$I->clickJS('.votingDataOpener');
$I->seeElement('.votingDataHolder');
$I->fillField('#votesYes', '15');
$I->fillField('#votesNo', '5');
$I->fillField('#votesAbstention', '2');
$I->fillField('#votesInvalid', '0');
$I->fillField('#votesComment', 'Accepted by mayority');

$I->submitForm('#motionUpdateForm', [], 'save');
$I->seeElement('.votingDataHolder');

$I->gotoMotion(2);

$I->see('Accepted by mayority', '.votingResultRow');
$I->see('Ja: 15, Nein: 5, Enthaltungen: 2, Ungültig: 0', '.votingResultRow');


$I->wantTo('enter a voting result for an amendment');

$I->gotoMotionList()->gotoAmendmentEdit(273);

$I->dontSeeElement('.votingDataHolder');
$I->clickJS('.votingDataOpener');
$I->seeElement('.votingDataHolder');
$I->fillField('#votesYes', '5');
$I->fillField('#votesNo', '7');
$I->fillField('#votesAbstention', '');
$I->fillField('#votesInvalid', '1');
$I->fillField('#votesComment', 'Rejected');

$I->submitForm('#amendmentUpdateForm', [], 'save');

$I->gotoAmendment(true, '321-o-zapft-is', 273);

$I->see('Rejected', '.votingResultRow');
$I->see('Ja: 5, Nein: 7, Ungültig: 1', '.votingResultRow');
