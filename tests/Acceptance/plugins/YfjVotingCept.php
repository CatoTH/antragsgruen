<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBDataYfj();

$I->gotoConsultationHome(true, 'std', 'yfj-test');

$I->wantTo('Create and open a Roll Call');
$I->loginAsStdAdmin();
$I->click('#votingsLink');
$I->see('No votings are open');
$I->click('#sidebar .admin a');
$I->wait(0.2);

$I->clickJS('.createRollCall');
$I->fillField('#roll_call_number', '1');
$I->fillField('#roll_call_name', 'Friday evening');
$I->seeCheckboxIsChecked('#roll_call_create_groups');
$I->submitForm('.createRollCallForm', [], null);
$I->wait(0.2);

$I->see('Roll Call 1 (Friday evening)', '#voting1');
$I->see('Simple majority (15 out of 29 users)', '#voting1');
$I->see('Voting IS set up as YFJ Roll Call', '#voting1');

$I->clickJS('#voting1 .btnOpen');
$I->wait(0.2);

$I->logout();


$I->wantTo('Participate as a full member');
$I->loginAsYfjUser('ingyo-full', 0);
$I->wait(0.2);
$I->clickJS('.voting_question_1 .btnPresent');
$I->wait(0.2);
$I->see('1 user has marked their presence', '.voting');

$present = [
    'ingyo-full' => 7, // (7 + 1) / 10
    'nyc-full' => 7,   //       7 / 8
    'ingyo-full-nov' => 1,   // 1 / 2
    'nyc-full-nov' => 0,     // 0 / 1
    'ingyo-ob' => 1,         // 1 / 2
    'nyc-ob' => 1,           // 1 / 2
    'ingyo-can' => 1,        // 1 / 2
    'nyc-can' => 1,          // 1 / 2
];
foreach ($present as $orgaName => $number) {
    for ($i = 1; $i <= $number; $i++) {
        $email = $orgaName . '-' . $i . '@example.org';
        $I->apiSetUserVoted('std', 'yfj-test', $email, 1, 1, 'present');
    }
}


$I->wantTo('check the results as admin');
$I->logout();
$I->loginAsStdAdmin();
$I->click('#votingsLink');
$I->see('20 presences have been marked');
$I->click('#sidebar .admin a');
$I->wait(0.2);

$I->see('16 out of 15 necessary votes'); // @TODO check if this is correct (16 vs. 14 votes)

$I->clickJS('#voting1 .btnClose');
$I->wait(0.2);
$I->see('Quorum reached');


$I->wantTo('move the participants of the Roll call to the voting list');
$I->clickJS('#voting1 .btnShowVotes');
// NYC
$I->clickJS('#voting1 .voteListHolder49 .userGroupSetter .userGroupSetterOpener');
$I->selectOption('#voting1 .voteListHolder49 .userGroupSetter select', '64');
$I->clickJS('#voting1 .voteListHolder49 .userGroupSetterDo');
// INGYO
$I->clickJS('#voting1 .voteListHolder48 .userGroupSetter .userGroupSetterOpener');
$I->selectOption('#voting1 .voteListHolder48 .userGroupSetter select', '65');
$I->clickJS('#voting1 .voteListHolder48 .userGroupSetterDo');


$I->wantTo('Create an actual voting');
$I->clickJS('.votingOperations .createYfjVoting');
$I->fillField('#voting_number', '1');
$I->fillField('#voting_title', 'Should we agree?');
$I->submitForm('.createYfjVotingForm', [], '');

$I->wait(0.2);
$I->see('Should we agree?', '#voting2');
$I->see('Who may vote: Voting 1: NYC, Voting 1: INGYO', '#voting2');
$I->clickJS('#voting2 .btnOpen');
$I->wait(0.2);

$I->wantTo('submit a few votes from members');
$I->apiSetUserVoted('std', 'yfj-test', 'ingyo-full-1@example.org', 2, 2, 'yes');
$I->apiSetUserVoted('std', 'yfj-test', 'ingyo-full-2@example.org', 2, 2, 'yes');
$I->apiSetUserVoted('std', 'yfj-test', 'ingyo-full-3@example.org', 2, 2, 'no');
$I->apiSetUserVoted('std', 'yfj-test', 'nyc-full-1@example.org', 2, 2, 'yes');
$I->apiSetUserVoted('std', 'yfj-test', 'nyc-full-2@example.org', 2, 2, 'yes');
$I->apiSetUserVoted('std', 'yfj-test', 'nyc-full-3@example.org', 2, 2, 'no');

$I->reloadPage();
$I->wait(0.2);

// @TODO Test the results
