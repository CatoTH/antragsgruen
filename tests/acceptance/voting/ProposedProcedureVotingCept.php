<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('enable non-quota speech lists');
$I->gotoConsultationHome();
$I->dontSeeElement('.currentSpeechInline'); // @TODO
$I->dontSeeElement('#speechAdminLink');

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
// @TODO Sill no see

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

$I->dontSeeElement('.voting1 .btnOpen');
$I->seeElement('.voting1 .btnClose');
$I->seeElement('.voting1 .btnReset');
$I->seeElement('.voting1 .voting_amendment_3 .votingTableSingle');

$I->see('0', '.voting_amendment_3 .voteCountNo');
$I->see('0', '.voting_amendment_3 .voteCountNo');
$I->see('0', '.voting_amendment_3 .voteCountAbstention');
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

$I->see('1', '.voting_amendment_3 .voteCountYes');
$I->see('0', '.voting_amendment_3 .voteCountNo');
$I->see('0', '.voting_amendment_3 .voteCountAbstention');
$I->see('1', '.voting_amendment_3 .voteCountTotal');
$I->see('0', '.voting_amendment_274 .voteCountYes');
$I->see('1', '.voting_amendment_274 .voteCountNo');


// @TODO Resetting it

$I->wantTo('Close the voting');

$I->clickJS('.voting1 .btnClose');
$I->wait(0.3);

$I->see('Angenommen', '.voting_amendment_3');
$I->see('Abgelehnt', '.voting_amendment_274');