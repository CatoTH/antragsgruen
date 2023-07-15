<?php

/** @var \Codeception\Scenario $scenario */
use app\models\votings\AnswerTemplates;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->loginAndGotoStdAdminPage()->gotoVotingPage();

$I->dontSeeElement('.quorumCounter');

$I->wantTo('Create a voting with a question');
$I->dontSeeElement('form.creatingVoting');
$I->clickJS('.createVotingOpener');
$I->seeElement('form.creatingVoting');

$I->assertSame('question', $I->executeJS('return $("input[name=votingTypeNew]:checked").val()'));
$I->clickJS('input[name=votingTypeNew][value=motions]');
$I->dontSeeElement('form.creatingVoting .specificQuestion');
$I->clickJS('input[name=votingTypeNew][value=question]');
$I->seeElement('form.creatingVoting .specificQuestion');

$I->fillField('.creatingVoting .settingsTitle', 'Vote on these questions');
$I->fillField('.creatingVoting .settingsQuestion', 'Is this cool?');
$I->assertSame(AnswerTemplates::TEMPLATE_YES_NO_ABSTENTION, (int)$I->executeJS('return $("input[name=answersNew]:checked").val()'));
$I->assertSame(1, (int)$I->executeJS('return $("input[name=resultsPublicNew]:checked").val()'));
$I->assertSame(0, (int)$I->executeJS('return $("input[name=votesPublicNew]:checked").val()'));
$I->clickJS('input[name=votesPublicNew][value=\"1\"]');
$I->clickJS('form.creatingVoting button[type=submit]');

$I->wait(0.3);


$I->wantTo('see that the voting was created successfully');
$votingId = '#voting' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID;
$I->seeElement($votingId);
$I->see('Vote on these questions', $votingId . ' h2');
$I->see('Einfache Mehrheit', $votingId . ' .majorityType');
$I->see('Is this cool?', $votingId . ' .voting_question_1 .titleLink');


$I->wantTo('Add another question (and remove one more)');
$I->dontSeeElement('#voting_question_' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID);
$I->clickJS($votingId . ' .addingItemsForm .addQuestions');
$I->fillField('#voting_question_' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID, 'Do you agree?');
$I->clickJS($votingId . ' .addingQuestions button[type=submit]');
$I->wait(0.3);

$I->clickJS($votingId . ' .addingItemsForm .addQuestions');
$I->fillField('#voting_question_' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID, 'One too much');
$I->clickJS($votingId . ' .addingQuestions button[type=submit]');
$I->wait(0.3);

$I->see('Do you agree?', $votingId . ' .voting_question_2 .titleLink');
$I->see('One too much', $votingId . ' .voting_question_3 .titleLink');

$I->clickJS($votingId . ' .voting_question_3 .removeBtn');

$I->wait(0.3);

$I->see('Do you agree?', $votingId . ' .voting_question_2 .titleLink');
$I->dontSee('One too much', $votingId . ' .voting_question_3 .titleLink');


$I->wantTo('Open the voting and participate');

$I->clickJS($votingId . ' .btnOpen');

$I->gotoConsultationHome();
$I->see('Is this cool', '.voting_question_1');
$I->see('Do you agree', '.voting_question_2');
$I->dontSee('One too much');
$I->clickJS('.voting_question_1 .btnYes');
$I->clickJS('.voting_question_2 .btnAbstention');
$I->wait(0.3);
$I->seeElement('.voting_question_1 span.yes');
$I->seeElement('.voting_question_2 span.abstention');


$I->wantTo('Finish the voting');
$I->click('.votingsAdminLink');

$I->see('1', '.voting_question_1 .voteCount_yes');
$I->see('1', '.voting_question_2 .voteCount_abstention');

$I->clickJS($votingId . ' .btnClose');
$I->wait(0.3);

$I->seeElement('.voting_question_1 .result .accepted');
$I->seeElement('.voting_question_2 .result .rejected');
