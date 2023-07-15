<?php

/** @var \Codeception\Scenario $scenario */
use app\models\votings\AnswerTemplates;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->loginAndGotoStdAdminPage()->gotoVotingPage();

$I->wantTo('Create a voting with a question');
$I->dontSeeElement('form.creatingVoting');
$I->clickJS('.createVotingOpener');
$I->seeElement('form.creatingVoting');

$I->assertSame('question', $I->executeJS('return $("input[name=votingTypeNew]:checked").val()'));
$I->fillField('.creatingVoting .settingsTitle', 'Pick your two favorite animals');
$I->fillField('.creatingVoting .settingsQuestion', 'Dog');
$I->seeElement('.creatingVoting .majorityTypeSettings');
$I->clickJS('input[name=answersNew][value=\"' . AnswerTemplates::TEMPLATE_YES . '\"]');
$I->dontSeeElement('.creatingVoting .majorityTypeSettings');
$I->clickJS('form.creatingVoting button[type=submit]');

$I->wait(0.3);


$I->wantTo('see that the voting was created successfully');
$votingId = '#voting' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID;
$I->seeElement($votingId);
$I->see('Pick your two favorite animals', $votingId . ' h2');
$I->dontSeeElement($votingId . ' .majorityType');
$I->see('Dog', $votingId . ' .voting_question_1 .titleLink');

$I->wantTo('Add more animals');
foreach (['Cat', 'Cow', 'Elephant'] as $animal) {
    $I->dontSeeElement('#voting_question_' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID);
    $I->clickJS($votingId . ' .addingItemsForm .addQuestions');
    $I->fillField('#voting_question_' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID, $animal);
    $I->clickJS($votingId . ' .addingQuestions button[type=submit]');
    $I->wait(0.2);
}
$I->see('Cat', $votingId . ' .voting_question_2 .titleLink');
$I->see('Cow', $votingId . ' .voting_question_3 .titleLink');
$I->see('Elephant', $votingId . ' .voting_question_4 .titleLink');

$I->wantTo('Set the vote limit');
$I->clickJS($votingId . ' .settingsToggleGroup .btn');
$I->assertSame('0', $I->executeJS('return $("' . $votingId . ' .votesMaxVotes input:checked").val()'));
$I->dontSeeElement($votingId . ' .votesMaxVotesAll');
$I->clickJS($votingId . ' .votesMaxVotes .maxVotesAll input'); // Global limit. @TODO Also add a test for per-group limits
$I->seeElement($votingId . ' .votesMaxVotesAll');
$I->executeJS('window.votingAdminWidget.$refs["voting-admin-widget"][1].setMaxVotesRestrictionAll("2")');
$I->clickJS($votingId . ' .votingSettings .btnSave');
$I->wait(0.3);

$I->wantTo('Open the voting and participate');
$I->clickJS($votingId . ' .btnOpen');

$I->gotoConsultationHome();
$I->see('Elephant', '.voting_question_4');
$I->seeElement('.voting_question_1 .btnYes');
$I->dontSeeElement('.voting_question_1 .btnNo');

// The choice here is pretty obvios
$I->see('Du hast noch 2 Stimmen zu vergeben.', '.currentVoting');
$I->clickJS('.voting_question_3 .btnYes');
$I->wait(0.3);

$I->see('Du hast noch 1 Stimme zu vergeben.', '.currentVoting');
$I->seeElement('.voting_question_1 .btnYes');
$I->clickJS('.voting_question_4 .btnYes');
$I->wait(0.3);

$I->see('Du hast alle Stimmen abgegeben.', '.currentVoting');
$I->dontSeeElement('.voting_question_1 .btnYes');
$I->dontSeeElement('.voting_question_2 .btnYes');
$I->dontSeeElement('.voting_question_3 .btnYes');
$I->dontSeeElement('.voting_question_4 .btnYes');


$I->wantTo('Finish the voting');
$I->click('.votingsAdminLink');

$I->see('0', '.voting_question_1 .voteCount_yes');
$I->see('0', '.voting_question_2 .voteCount_yes');
$I->see('1', '.voting_question_3 .voteCount_yes');
$I->see('1', '.voting_question_4 .voteCount_yes');
$I->dontSeeElement('.voting_question_4 .voteCount_no');
$I->dontSeeElement('.voting_question_4 .voteCount_abstention');

$I->clickJS($votingId . ' .btnClose');
$I->wait(0.3);

$I->dontSeeElement('.voting_question_4 .result');
