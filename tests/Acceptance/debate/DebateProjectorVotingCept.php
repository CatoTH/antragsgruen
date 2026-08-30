<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

// The std-parteitag fixture ships with an ongoing debate on motion A2 ("O’zapft is!").
// Give that motion a voting and open it, so that the debate widget embeds it.
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->gotoConsultationHome();
$I->waitForElement('.currentDebateAdmin .debatedItem', 5);
$I->click('.currentDebateAdmin .manageVotingBtn'); // creates the voting for the debated motion
$I->waitForElement('.currentDebateAdmin .votingTab .votingCard', 8);

$votingId = '#voting' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID;
$I->gotoStdAdminPage()->gotoVotingPage();

// Give it a vote limit, so that the widget knows how many votes each account still has - the number
// that must not end up on the projection. Has to happen before opening: settings are frozen then.
$I->clickJS($votingId . ' .settingsToggleGroup .btn');
$I->clickJS($votingId . ' .votesMaxVotes .maxVotesAll input');
$I->seeElement($votingId . ' .votesMaxVotesAll');
// Addressed by id rather than by position: the widgets are not in id order on the page
$I->executeJS(
    'window.votingAdminWidget.$refs["voting-admin-widget"]' .
    '.find(widget => widget.voting.id === ' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID . ')' .
    '.setMaxVotesRestrictionAll("2")'
);
$I->clickJS($votingId . ' .votingSettings .btnSave');
$I->wait(0.3);

$I->clickJS($votingId . ' .btnOpen');
$I->wait(0.3);


$I->wantTo('project the voting without anything that only concerns the person at the browser');
$I->gotoConsultationHome();
$I->waitForElement('.currentDebateAdmin .debatedItem', 5);
$I->click('.currentDebateAdmin .btnFullscreen');
$projectedVoting = '.fullscreenMainHolder .currentDebateVoting';
$I->waitForElement($projectedVoting . ' .voting', 8);

// How many votes were cast is about the vote and stays...
$I->see('Status', $projectedVoting . ' .votedCounter');
// ...but how many votes *this* account has left, and its own vote weight, do not belong on a wall
$I->dontSee('Du hast', $projectedVoting);
$I->dontSeeElement($projectedVoting . ' .votingWeight');
$I->dontSeeElement($projectedVoting . ' .votingOptions');
// Neither do the links out of the projection: into the voting administration, or to the motion
$I->dontSeeElement($projectedVoting . ' .votingsAdminLink');
$I->dontSeeElement($projectedVoting . ' .glyphicon-new-window');

// Navigating away tears the projector down; closing it via its button is covered by DebateWidgetsCept
$I->gotoConsultationHome();


$I->wantTo('still see those elements in the regular widget, which is about the person reading it');
$I->logout();
$I->loginAsStdUser();
$I->gotoConsultationHome();
$inlineVoting = '.currentDebateInline .currentDebateVoting';
$I->waitForElement($inlineVoting . ' .voting', 8);
$I->seeElement($inlineVoting . ' .votingOptions');
$I->seeElement($inlineVoting . ' .glyphicon-new-window');
$I->see('Du hast noch 2 Stimmen zu vergeben.', $inlineVoting . ' .votedCounter');
