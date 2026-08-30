<?php

/** @var \Codeception\Scenario $scenario */

use app\models\votings\AnswerTemplates;
use Tests\_pages\VotingResultsPage;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->loginAndGotoStdAdminPage();
// The fixture has the "Currently debated" module on, which would take the place of the voting widget
$I->disableCurrentlyDebated();
$I->gotoStdAdminPage()->gotoVotingPage();

$I->wantTo('Create a roll call voting');
$I->dontSeeElement('form.creatingVoting');
$I->clickJS('.createVotingOpener');
$I->seeElement('form.creatingVoting');

$I->assertSame('question', $I->executeJS('return $("input[name=votingTypeNew]:checked").val()'));
$I->fillField('.creatingVoting .settingsTitle', 'Roll call');
$I->fillField('.creatingVoting .settingsQuestion', 'Who is present?');
$I->seeElement('.majorityTypeSettings');

$I->clickJS("input[name=answersNew][value='" . AnswerTemplates::TEMPLATE_PRESENT . "']");
$I->dontSeeElement('.majorityTypeSettings');
$I->assertSame(1, (int)$I->executeJS('return $("input[name=resultsPublicNew]:checked").val()'));
$I->clickJS('input[name=votesPublicNew][value=\"2\"]');
$I->clickJS('input[name=resultsPublicNew][value=\"1\"]');
$I->clickJS('form.creatingVoting button[type=submit]');
$I->wait(0.3);


$I->wantTo('see that the voting was created successfully and enable it');
$votingId = '#voting' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID;
$I->seeElement($votingId);
$I->see('Roll call', $votingId . ' h2');
$I->dontSeeElement($votingId . ' .majorityType');
$I->see('Who is present?', $votingId . ' .voting_question_1 .titleLink');
$I->clickJS($votingId . ' .btnOpen');


$I->wantTo('Participate at the roll call');
$I->gotoConsultationHome();
$I->see('Roll call', 'h2');
$I->see('Who is present?', '.voting_question_1');
$I->clickJS('.voting_question_1 .btnPresent');
$I->wait(0.3);
$I->seeElement('.voting_question_1 span.present');


$I->wantTo('Finish the voting, but don\'t publish the results right away');
$I->click('.votingsAdminLink');

$I->see('1', '.voting_question_1 .voteCount_present');
$I->dontSeeElement('.voting_question_1 .result .accepted');
$I->dontSeeElement('.voteResults');
$I->clickJS('.voting_question_1 .btnShowVotes');
$I->see('testadmin@example.org', '.voteResults');

$I->dontSeeElement($votingId . ' .btnPublish');
$I->dontSeeElement($votingId . ' .btnCloseNopub');
$I->clickJS($votingId . ' .btnClosePubOpener');
$I->seeElement($votingId . ' .btnCloseNopub');
$I->clickJS($votingId . ' .btnCloseNopub');

$I->wait(0.3);
$I->seeElement($votingId . ' .btnPublish');


$I->wantTo('not see it on the home page nor on the results page');
$I->gotoConsultationHome();
$I->dontSeeElement('.voting_question_1');

$I->openPage(VotingResultsPage::class, [
    'subdomain'        => 'stdparteitag',
    'consultationPath' => 'std-parteitag',
]);
$I->seeElement('.votingsNoneIndicator');
$I->dontSeeElement('.voting_question_1');


$I->wantTo('publish the results');
$I->gotoStdAdminPage()->gotoVotingPage();
$I->clickJS($votingId . ' .btnPublish');
$I->wait(0.3);
$I->dontSeeElement($votingId . ' .btnPublish');


$I->wantTo('not see it on the home page, but in the results');
$I->gotoConsultationHome();
$I->dontSeeElement('.voting_question_1');

$I->logout();
$I->click('#votingResultsLink');
$I->see('Login', 'h1');

$I->loginAsStdUser();
$I->click('#votingResultsLink');
$I->wait(0.3);
$I->dontSeeElement('.votingsNoneIndicator');
$I->see('1', '.voting_question_1 .voteCount_present');
$I->dontSeeElement('.voting_question_1 .result .accepted');
$I->dontSeeElement('.regularVoteList');
$I->clickJS('.voting_question_1 .btnShowVotes');
$I->wait(0.3);
$I->see('testadmin@example.org', '.regularVoteList');



$json = $I->executeJS('return document.querySelector(".currentVotingWidget").getAttribute("data-voting")');
$jsonParsed = json_decode($json, true);
$I->assertJsonStringEqualsJsonString('[
  {
    "id": ' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID . ',
    "title": "Roll call",
    "status": "closed_published",
    "current_time": ' . $jsonParsed[0]['current_time'] . ',
    "answers": [ { "api_id": "present", "title": "Anwesend", "result": null } ],
    "has_majority": false,
    "is_presence_call": true,
    "publicity": { "single_votes": "everybody", "results": "everybody" },
    "statistics": { "votes": 1, "voters": 1 },
    "item_groups": [
      {
        "id": "single:question:1",
        "items": [ { "type": "question", "id": 1 } ],
        "name": null,
        "results": {
          "counts": [ { "answers": [ { "answer": "present", "votes": 1 } ], "organization": null } ],
          "quorum": null
        },
        "single_votes": [
          {
            "answer": "present",
            "weight": 1,
            "voter": { "user_id": 1, "user_group_ids": [1], "user_name": "testadmin@example.org" }
          }
        ]
      }
    ],
    "items": [
      {
        "type": "question",
        "id": 1,
        "group_id": "single:question:1",
        "title_with_prefix": "Who is present?",
        "prefix": null,
        "initiators_html": null,
        "url_html": null,
        "url_json": null,
        "procedure_html": null,
        "result": null
      }
    ],
    "me": {
      "eligible": true,
      "vote_weight": 1,
      "abstained": false,
      "votes": [],
      "can_vote_group_ids": [],
      "votes_remaining": null
    },
    "assigned_motion_id": null,
    "opened_at": null,
    "voting_time": null,
    "quorum": null,
    "abstention": { "enabled": false, "count": null, "users": null },
    "policy": { "id": 2, "description": "Eingeloggte", "user_groups": null },
    "user_groups": [
      { "id": 1, "title": "Seiten-Admin", "member_count": 2 },
      { "id": 2, "title": "Veranstaltungs-Admin", "member_count": 1 },
      { "id": 3, "title": "Antragskommission", "member_count": 1 },
      { "id": 4, "title": "Teilnehmer*in", "member_count": 0 },
      { "id": 39, "title": "Sachst\u00e4nde bearbeiten", "member_count": 1 }
    ]
  }
]', $json);
