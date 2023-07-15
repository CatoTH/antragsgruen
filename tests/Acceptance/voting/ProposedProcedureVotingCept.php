<?php

use app\models\db\VotingBlock;
use Tests\Support\AcceptanceTester;

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('enable non-quota speech lists');
$I->gotoConsultationHome();
$I->dontSeeElementInDOM('.currentVotings');
$I->dontSeeElementInDOM('.voting_amendment_3');
$I->dontSeeElementInDOM('#votingResultsLink');

$votingPage = $I->loginAsStdAdmin()->gotoStdAdminPage()->gotoVotingPage();

$I->see('Ä2 or Ä3', '.voting1 h2');
$I->see('Ä2 zu A2', '.voting1 .voting_amendment_3');
// Voting is still in offline mode
$I->dontSeeCheckboxIsChecked('.voting1 .activateHeader input');
$I->dontSeeElement('.voting1 .btnOpen');
$I->dontSeeElement('.voting1 .voting_amendment_270 .removeBtn');


$I->wantTo('Enable online voting for this voting');
$I->clickJS('.voting1 .activateHeader input');
$I->wait(0.3);
$I->seeElement('.voting1 .btnOpen');

$I->gotoConsultationHome();
$I->seeElementInDOM('.currentVotings'); // It is not visible yet, but polling is already active
$I->dontSeeElementInDOM('.voting_amendment_3');

$I->gotoStdAdminPage()->gotoVotingPage();
$I->seeCheckboxIsChecked('.voting1 .activateHeader input');


$I->wantTo('Remove Ä3');
$I->seeElement('.voting1 .voting_amendment_270');
$I->seeElement('.voting1 .voting_amendment_270 .removeBtn');
$I->clickJS('.voting1 .voting_amendment_270 .removeBtn');
$I->wait(0.3);
$I->dontSeeElement('.voting1 .voting_amendment_270');

$I->wantTo('Open the voting');
$I->dontSeeElement('.voting1 .btnClose');
$I->dontSeeElement('.voting1 .btnReset');
$I->dontSeeElement('.voting1 .voting_amendment_3 .votingTableSingle');
$I->clickJS('.voting1 .btnOpen');
$I->wait(0.3);


$I->wantTo('hide the numeric results from the voting');
$I->dontSeeElement('#voting1 .titleSetting');
$I->clickJS('#voting1 .settingsToggleGroup button');
$I->seeElement('#voting1 .titleSetting');
$I->assertEquals(VotingBlock::RESULTS_PUBLIC_YES, $I->executeJS('return $("#voting1 .resultsPublicSettings input[type=radio]:checked").val()'));
$I->assertTrue($I->executeJS('return $("#voting1 .votesPublicSettings input[type=radio]").prop("disabled")'));
$I->clickJS('#voting1 .resultsPublicSettings input[type=radio][value=\"' . VotingBlock::RESULTS_PUBLIC_NO . '\"]');
$I->clickJS('#voting1 .btnSave');
$I->wait(0.3);


$I->dontSeeElement('.voting1 .btnOpen');
$I->seeElement('.voting1 .btnClose');
$I->seeElement('.voting1 .btnReset');
$I->seeElement('.voting1 .voting_amendment_3 .votingTableSingle');

$I->see('0', '.voting_amendment_3 .voteCount_no');
$I->see('0', '.voting_amendment_3 .voteCount_no');
$I->see('0', '.voting_amendment_3 .voteCount_abstention');
$I->see('0', '.voting_amendment_3 .voteCountTotal');

$I->wantTo('Vote no, but correct it to yes');
$I->gotoConsultationHome();
$I->seeElement('.currentVotings');
$I->seeElement('.voting_amendment_3');
$I->dontSeeElement('.voting_amendment_270');
$I->seeElement('.voting_amendment_3 .btnNo');
$I->clickJS('.voting_amendment_3 .btnNo');
$I->wait(0.3);

$I->see('Nein', '.voting_amendment_3 .voted');
$I->dontSeeElement('.voting_amendment_3 .btnNo');
$I->clickJS('.voting_amendment_3 .btnUndo');
$I->wait(0.3);

$I->seeElement('.voting_amendment_3 .btnNo');
$I->clickJS('.voting_amendment_3 .btnYes');
$I->clickJS('.voting_amendment_274 .btnNo');
$I->wait(0.3);

$I->see('Ja', '.voting_amendment_3 .voted');


$I->wantTo('See the updated results');
$I->click('.votingsAdminLink');
$I->wait(0.3);

$I->see('1', '.voting_amendment_3 .voteCount_yes');
$I->see('0', '.voting_amendment_3 .voteCount_no');
$I->see('0', '.voting_amendment_3 .voteCount_abstention');
$I->see('1', '.voting_amendment_3 .voteCountTotal');
$I->see('0', '.voting_amendment_274 .voteCount_yes');
$I->see('1', '.voting_amendment_274 .voteCount_no');


// @TODO Resetting it

$I->wantTo('Close the voting');

$I->clickJS('.voting1 .btnClose');
$I->wait(0.3);

$I->see('Angenommen', '.voting_amendment_3');
$I->see('Abgelehnt', '.voting_amendment_274');


$I->wantTo('see the voting result on the public page');
$I->gotoConsultationHome();
$I->click('#votingResultsLink');
$I->wait(0.5);
$I->dontSeeElement('.voting_motion_114 .votingTableSingle');
$I->seeElement('.voting_amendment_3 .accepted');
$I->seeElement('.voting_amendment_274 .rejected');
