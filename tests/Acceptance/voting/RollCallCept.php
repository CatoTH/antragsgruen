<?php

/** @var \Codeception\Scenario $scenario */

use app\models\votings\AnswerTemplates;
use Tests\_pages\VotingResultsPage;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->loginAndGotoStdAdminPage()->gotoVotingPage();

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
    "id": "' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID . '",
    "title": "Roll call",
    "status": 3,
    "votes_public": 2,
    "votes_names": 0,
    "results_public": 1,
    "assigned_motion": null,
    "majority_type": 1,
    "quorum_type": 0,
    "user_groups": [
      { "id": 1, "title": "Seiten-Admin", "member_count": 2 },
      { "id": 2, "title": "Veranstaltungs-Admin", "member_count": 1 },
      { "id": 3, "title": "Antragskommission", "member_count": 1 },
      { "id": 4, "title": "Teilnehmer*in", "member_count": 0 },
      { "id": 39, "title": "Sachst\u00e4nde bearbeiten", "member_count": 1 }
    ],
    "answers": [ { "api_id": "present", "title": "Anwesend", "status_id": null } ],
    "answers_template": 2,
    "items": [
      {
        "type": "question",
        "id": 1,
        "prefix": "",
        "title_with_prefix": "Who is present?",
        "url_json": null,
        "url_html": null,
        "initiators_html": null,
        "procedure": null,
        "item_group_same_vote": null,
        "item_group_name": null,
        "voting_status": null,
        "vote_results": [
          {
            "present": 1
          }
        ],
        "vote_eligibility": null,
        "votes": [
          {
            "user_id": 1,
            "user_groups": [1],
            "user_name": "testadmin@example.org",
            "vote": "present",
            "weight": 1
          }
        ]
      }
    ],
    "current_time": ' . $jsonParsed[0]['current_time'] . ',
    "voting_time": null,
    "opened_ts": null,
    "abstentions_total": 0,
    "has_general_abstention": false,
    "votes_total": 1,
    "votes_users": 1,
    "vote_policy": { "id": 2, "description": "Eingeloggte" }
  }
]', $json);
