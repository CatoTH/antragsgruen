<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->loginAndGotoStdAdminPage()->gotoVotingPage();

$I->dontSeeElement('.votingOperations .sortVotings');
$I->dontSeeElement('.votingSorting');

$votingId1 = 1;
$votingId2 = AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID;
$votingId3 = AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID + 1;

$I->wantTo('Create two votings with a question each');
$I->dontSeeElement('form.creatingVoting');
$I->clickJS('.createVotingOpener');
$I->fillField('.creatingVoting .settingsTitle', 'Vote on question 1');
$I->fillField('.creatingVoting .settingsQuestion', 'Question 1?');
$I->clickJS('form.creatingVoting button[type=submit]');
$I->wait(0.3);
$I->see('Question 1?', '.voting'. AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID);

$I->dontSeeElement('form.creatingVoting');
$I->clickJS('.createVotingOpener');
$I->fillField('.creatingVoting .settingsTitle', 'Vote on question 2');
$I->fillField('.creatingVoting .settingsQuestion', 'Question 2?');
$I->clickJS('form.creatingVoting button[type=submit]');

$I->wait(0.3);
$I->see('Question 2?', '.voting'. (AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID + 1));


$I->wantTo('check the sorting is possible');
$I->seeElement('.votingOperations .sortVotings');
$I->clickJS('.votingOperations .sortVotings');
$I->wait(0.1);
$I->seeElement('.votingSorting');
$I->see('Ä2 or Ä3', '.votingSorting .list-group-item');
$I->see('Vote on question 1', '.votingSorting .list-group-item');
$I->see('Vote on question 2', '.votingSorting .list-group-item');

$I->assertEquals([$votingId3, $votingId2, $votingId1], $I->executeJS("return window.votingAdminWidget.\$refs['voting-sort-widget'].getSortedIds()"));
$I->executeJS("return window.votingAdminWidget.\$refs['voting-sort-widget'].setOrder([" . $votingId3 . ", " . $votingId1 . ", " . $votingId2 . "])");
$I->assertEquals([$votingId3, $votingId1, $votingId2], $I->executeJS("return window.votingAdminWidget.\$refs['voting-sort-widget'].getSortedIds()"));
$I->clickJS('.votingSorting .btnSave');


$I->wantTo('check that the order persists');
$I->gotoStdAdminPage()->gotoVotingPage();
$I->wait(0.3);
$I->clickJS('.votingOperations .sortVotings');
$I->wait(0.1);
$I->assertEquals([$votingId3, $votingId1, $votingId2], $I->executeJS("return window.votingAdminWidget.\$refs['voting-sort-widget'].getSortedIds()"));
