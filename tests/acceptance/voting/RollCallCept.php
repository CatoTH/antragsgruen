<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->loginAndGotoStdAdminPage()->gotoVotingPage();

$I->wantTo('Create a roll call voting');
$I->dontSeeElement('form.creatingVoting');
$I->clickJS('.createVotingOpener');
$I->seeElement('form.creatingVoting');

// @TODO Test that majority is disappearing for only one option

$I->assertSame('question', $I->executeJS('return $("input[name=votingTypeNew]:checked").val()'));
$I->fillField('.creatingVoting .settingsTitle', 'Roll call');
$I->fillField('.creatingVoting .settingsQuestion', 'Who is present?');
$I->clickJS('input[name=answersNew][value=\"' . \app\models\votings\AnswerTemplates::TEMPLATE_PRESENT . '\"]');
$I->assertSame('1', $I->executeJS('return $("input[name=resultsPublicNew]:checked").val()'));
$I->clickJS('input[name=votesPublicNew][value=\"2\"]');
$I->clickJS('form.creatingVoting button[type=submit]');

$I->wait(0.3);

$I->wantTo('see that the voting was created successfully and enable it');
$votingId = '#voting' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID;
$I->seeElement($votingId);
$I->see('Roll call', $votingId . ' h2');
//$I->dontSeeElement($votingId . ' .majorityType'); // @TODO Enable
$I->see('Who is present?', $votingId . ' .voting_question_1 .titleLink');

$I->clickJS($votingId . ' .btnOpen');
// @TODO See options
