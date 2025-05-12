<?php

/** @var \Codeception\Scenario $scenario */

use app\models\policies\IPolicy;
use app\models\policies\UserGroups;
use app\models\votings\AnswerTemplates;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$userGroupsJson = '{ "id": 1, "title": "Seiten-Admin", "member_count": 2 },
      { "id": 2, "title": "Veranstaltungs-Admin", "member_count": 1 },
      { "id": 3, "title": "Antragskommission", "member_count": 1 },
      { "id": 4, "title": "Teilnehmer*in", "member_count": 0 },
      { "id": 39, "title": "Sachst\u00e4nde bearbeiten", "member_count": 1 },
      { "id": ' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . ', "title": "Voting group", "member_count": 1 }';


$I->wantTo('Create a user group and assign testuser to it');
$I->loginAndGotoStdAdminPage()->gotoUserAdministration();
$I->clickJS('.btnGroupCreate');
$I->seeElement('.addGroupForm');
$I->fillField('.addGroupForm .addGroupName input', 'Voting group');
$I->clickJS('.addGroupForm .btnSave');
$I->wait(0.5);
$I->see('Voting group', '.group' . AcceptanceTester::FIRST_FREE_USERGROUP_ID);


$I->wantTo('create a voting for only this user group');
$I->gotoStdAdminPage()->gotoVotingPage();
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
$I->clickJS('input[name=votesPublicNew][value=\"1\"]');
$I->clickJS('input[name=resultsPublicNew][value=\"1\"]');

$I->dontSeeElement('.createVotingHolder .votePolicy .userGroupSelect');
$I->selectOption('.createVotingHolder .votePolicy .policySelect', IPolicy::POLICY_USER_GROUPS);
$I->wait(0.1);
$I->seeElement('.createVotingHolder .votePolicy .userGroupSelect');
$I->assertSame(0, $I->executeJS('return document.querySelector(".createVotingHolder .votePolicy .userGroupSelectList").selectize.items.length'));
$I->executeJS('document.querySelector(".createVotingHolder .votePolicy .userGroupSelectList").selectize.addItem(1)');
$I->assertSame(1, $I->executeJS('return document.querySelector(".createVotingHolder .votePolicy .userGroupSelectList").selectize.items.length'));

$I->clickJS('form.creatingVoting button[type=submit]');
$I->wait(0.3);

$votingBaseId = '#voting' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID;
$I->see('Seiten-Admin', $votingBaseId . ' .votingSettingsSummary .votingPolicy');
$I->clickJS($votingBaseId . ' .settingsToggleGroup button');
$I->seeOptionIsSelected($votingBaseId . ' .v-policy-select .stdDropdown', UserGroups::getPolicyName());
$I->seeElement($votingBaseId . ' .v-policy-select .selectize-control');
$selected = $I->executeJS('return votingAdminWidget.$refs["voting-admin-widget"][1].$refs["policy-select"].userGroups');
$I->assertSame([1], $selected);
$I->wait(0.1);
$I->seeElement($votingBaseId . ' .v-policy-select .selectize-control');
$I->executeJS('votingAdminWidget.$refs["voting-admin-widget"][1].$refs["policy-select"].setSelectedGroups([' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . '])');
$I->clickJS($votingBaseId . ' .btnSave');
$I->wait(0.3);
$I->see('Voting group', $votingBaseId . ' .votingSettingsSummary .votingPolicy');
$I->clickJS($votingBaseId . ' .btnOpen');



$I->wantTo('not be able to vote as a user');
$I->logout();
$I->gotoConsultationHome();
$I->loginAsStdUser();
$I->see('Roll call', 'h2');
$I->see('Who is present?', '.voting_question_1');
$I->dontSeeElement('.voting_question_1 .btnPresent');


$I->wantTo('assign the group to a user (Testuser), and give them additional voting weight');
$I->logout();
$I->loginAndGotoStdAdminPage()->gotoUserAdministration();
$I->dontSeeElement('.user2');
$I->clickJS('.addUsersOpener.email');
$I->fillField('#emailAddresses', 'testuser@example.org');
$I->fillField('#names', 'ignored');
$I->submitForm('.addUsersByLogin.multiuser', [], 'addUsers');
$I->wait(1);
$I->seeElement('.user2');
$I->dontSeeElement('.user2 .selectize-control');
$I->clickJS('.user2 .btnEdit');
$I->wait(0.5);
$I->seeElement('.editUserModal');
$I->clickJS('.editUserModal .userGroup4'); // Unselect participant
$I->clickJS('.editUserModal .userGroup' . AcceptanceTester::FIRST_FREE_USERGROUP_ID);
$I->seeInField('.editUserModal .inputVoteWeight', '1');
$I->fillField('.editUserModal .inputVoteWeight', '7');
$I->clickJS('.editUserModal .btnSave');
$I->wait(0.5);
$I->dontSee('Veranstaltungs-Admin', '.user2');
$I->dontSee('Teilnehmer*in', '.user2');
$I->see('Voting group', '.user2');



$I->wantTo('be able to vote as stduser now');
$I->logout();
$I->gotoConsultationHome();
$I->loginAsStdUser();
$I->see('Roll call', 'h2');
$I->see('Who is present?', '.voting_question_1');
$I->see('7', '.currentVotings .votingWeight');
$I->clickJS('.voting_question_1 .btnPresent');
$I->wait(0.3);
$I->seeElement('.voting_question_1 span.present');


$I->wantTo('check the REST response of the user endpoint');

$pollUrl = '/stdparteitag/rest/std-parteitag/votings/open?assignedToMotionId=';
$json = $I->executeJS('return await fetch("' . $pollUrl . '").then(ret => ret.text())');
$jsonParsed = json_decode($json, true);
$I->assertJsonStringEqualsJsonString('[
  {
    "id": "' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID . '",
    "title": "Roll call",
    "status": 2,
    "votes_public": 1,
    "votes_names": 0,
    "results_public": 1,
    "assigned_motion": null,
    "majority_type": 1,
    "quorum_type": 0,
    "user_groups": [' . $userGroupsJson . '],
    "answers": [
      { "api_id": "present", "title": "Anwesend", "status_id": null }
    ],
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
        "voted": "present",
        "can_vote": false
      }
    ],
    "current_time": ' . $jsonParsed[0]['current_time'] . ',
    "voting_time": null,
    "opened_ts": ' . $jsonParsed[0]['opened_ts'] . ',
    "abstentions_total": 0,
    "has_abstained": false,
    "has_general_abstention": false,
    "votes_total": 1,
    "votes_users": 1,
    "vote_policy": {
      "id": 6,
      "user_groups": [ ' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . ' ],
      "description": "Voting group"
    },
    "votes_remaining": null,
    "vote_weight": 7
  }
]', $json);


$I->wantTo('see the weighted vote in the admin backend');
$I->logout();
$I->loginAndGotoStdAdminPage()->gotoVotingPage();
$I->wait(0.3);
$I->clickJS('.voting_question_1 .btnShowVotes');
$I->see('testuser@example.org (×7)', '.voteListHolder' . AcceptanceTester::FIRST_FREE_USERGROUP_ID);
$I->see('7', '.voting_question_1 .voteCount_present');


$I->wantTo('check the REST response of the admin endpoint');

$pollUrl = '/stdparteitag/rest/std-parteitag/votings/admin';
$json = $I->executeJS('return await fetch("' . $pollUrl . '").then(ret => ret.text())');
$jsonParsed = json_decode($json, true);
$I->assertJsonStringEqualsJsonString('[
  {
    "id": "' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID . '",
    "title": "Roll call",
    "status": 2,
    "votes_public": 1,
    "votes_names": 0,
    "results_public": 1,
    "assigned_motion": null,
    "majority_type": 1,
    "quorum_type": 0,
    "user_groups": [' . $userGroupsJson . '],
    "answers": [
      { "api_id": "present", "title": "Anwesend", "status_id": null }
    ],
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
          { "present": 7 }
        ],
        "vote_eligibility": [
          {
            "id": 40,
            "title": "Voting group",
            "users": [
              { "user_id": 2, "user_name": "testuser@example.org", "weight": 7 }
            ]
          }
        ],
        "votes": [
          {
            "vote": "present",
            "weight": 7,
            "user_id": 2,
            "user_name": "testuser@example.org",
            "user_groups": [ ' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . ' ]
          }
        ]
      }
    ],
    "current_time": ' . $jsonParsed[0]['current_time'] . ',
    "voting_time": null,
    "opened_ts": ' . $jsonParsed[0]['opened_ts'] . ',
    "log": [ { "type": 1, "date": "' . $jsonParsed[0]['log'][0]['date'] . '" } ],
    "max_votes_by_group": null,
    "abstentions_total": 0,
    "has_general_abstention": false,
    "votes_total": 1,
    "votes_users": 1,
    "vote_policy": {
      "id": 6,
      "user_groups": [ ' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . ' ],
      "description": "Voting group"
    }
  },
  {
    "id": "1",
    "title": "\u00c42 or \u00c43",
    "status": 0,
    "votes_public": null,
    "votes_names": 0,
    "results_public": null,
    "assigned_motion": null,
    "majority_type": null,
    "quorum_type": null,
    "user_groups": [' . $userGroupsJson . '],
    "answers": [
      { "api_id": "yes", "title": "Ja", "status_id": 4 },
      { "api_id": "no", "title": "Nein", "status_id": 5 },
      { "api_id": "abstention", "title": "Enthaltung", "status_id": null }
    ],
    "answers_template": 0,
    "items": [
      {
        "type": "amendment",
        "id": 3,
        "prefix": "\u00c42",
        "title_with_prefix": "\u00c42 zu A2: O\u2019zapft is!",
        "url_json": "http:\/\/test.antragsgruen.test\/stdparteitag\/rest\/std-parteitag\/motion\/321-o-zapft-is\/amendment\/3",
        "url_html": "http:\/\/test.antragsgruen.test\/stdparteitag\/std-parteitag\/motion\/321-o-zapft-is\/amendment\/3",
        "initiators_html": "Testadmin",
        "procedure": "<p>Abstimmung<\/p>",
        "item_group_same_vote": null,
        "item_group_name": null,
        "voting_status": 11,
        "vote_results": [ { "yes": 0, "no": 0, "abstention": 0 } ],
        "vote_eligibility": null
      },
      {
        "type": "amendment",
        "id": 270,
        "prefix": "\u00c43",
        "title_with_prefix": "\u00c43 zu A2: O\u2019zapft is!",
        "url_json": "http:\/\/test.antragsgruen.test\/stdparteitag\/rest\/std-parteitag\/motion\/321-o-zapft-is\/amendment\/270",
        "url_html": "http:\/\/test.antragsgruen.test\/stdparteitag\/std-parteitag\/motion\/321-o-zapft-is\/amendment\/270",
        "initiators_html": "Tester",
        "procedure": "<p>Abstimmung<\/p>",
        "item_group_same_vote": null,
        "item_group_name": null,
        "voting_status": 11,
        "vote_results": [ { "yes": 0, "no": 0, "abstention": 0 } ],
        "vote_eligibility": null
      },
      {
        "type": "amendment",
        "id": 274,
        "prefix": "\u00c46",
        "title_with_prefix": "\u00c46 zu A2: O\u2019zapft is!",
        "url_json": "http:\/\/test.antragsgruen.test\/stdparteitag\/rest\/std-parteitag\/motion\/321-o-zapft-is\/amendment\/274",
        "url_html": "http:\/\/test.antragsgruen.test\/stdparteitag\/std-parteitag\/motion\/321-o-zapft-is\/amendment\/274",
        "initiators_html": "Tester",
        "procedure": "<p>Erledigt durch: <a href=\"\/stdparteitag\/std-parteitag\/motion\/321-o-zapft-is\/amendment\/270\">\u00c43 zu A2<\/a><\/p>",
        "item_group_same_vote": null,
        "item_group_name": null,
        "voting_status": null,
        "vote_results": [ { "yes": 0, "no": 0, "abstention": 0 } ],
        "vote_eligibility": null
      }
    ],
    "current_time": ' . $jsonParsed[1]['current_time'] . ',
    "voting_time": null,
    "opened_ts": null,
    "log": [],
    "max_votes_by_group": null,
    "abstentions_total": 0,
    "has_general_abstention": false,
    "votes_total": 0,
    "votes_users": 0,
    "vote_policy": { "id": 2, "description": "Eingeloggte" }
  }
]', $json);


$I->wantTo('close the voting and see results');
$I->clickJS('.voting' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID . ' .btnClose');
$I->clickJS('.sidebarActions .results a');
$I->see('7', '.voting_question_1 .voteCount_present');

$json = $I->executeJS('return document.querySelector(".currentVotingWidget").getAttribute("data-voting")');
$jsonParsed = json_decode($json, true);
$I->assertJsonStringEqualsJsonString('[
  {
    "id": "' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID . '",
    "title": "Roll call",
    "status": 3,
    "votes_public": 1,
    "votes_names": 0,
    "results_public": 1,
    "assigned_motion": null,
    "majority_type": 1,
    "quorum_type": 0,
    "user_groups": [' . $userGroupsJson . '],
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
            "present": 7
          }
        ],
        "vote_eligibility": [
          {
            "id": ' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . ',
            "title": "Voting group",
            "users": [
              { "user_id": 2, "user_name": "testuser@example.org", "weight": 7 }
            ]
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
    "vote_policy": { "id": 6, "user_groups": [ ' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . ' ], "description": "Voting group" }
  }
]', $json);
