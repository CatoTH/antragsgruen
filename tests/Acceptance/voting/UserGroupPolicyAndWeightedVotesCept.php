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
$I->loginAndGotoStdAdminPage();
// The fixture has the "Currently debated" module on, which would take the place of the voting widget
$I->disableCurrentlyDebated();
$I->gotoStdAdminPage()->gotoUserAdministration();
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
    "id": ' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID . ',
    "title": "Roll call",
    "status": "open",
    "current_time": ' . $jsonParsed[0]['current_time'] . ',
    "answers": [
      {
        "api_id": "present",
        "title": "Anwesend",
        "result": null
      }
    ],
    "has_majority": false,
    "is_presence_call": true,
    "publicity": {
      "single_votes": "admins",
      "results": "everybody"
    },
    "statistics": {
      "votes": 1,
      "voters": 1
    },
    "item_groups": [
      {
        "id": "single:question:1",
        "items": [
          {
            "type": "question",
            "id": 1
          }
        ],
        "name": null,
        "results": null,
        "single_votes": null
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
      "vote_weight": 7,
      "abstained": false,
      "votes": [
        {
          "group_id": "single:question:1",
          "answer": "present"
        }
      ],
      "can_vote_group_ids": [],
      "votes_remaining": null
    },
    "assigned_motion_id": null,
    "opened_at": ' . $jsonParsed[0]['opened_at'] . ',
    "voting_time": null,
    "quorum": null,
    "abstention": {
      "enabled": false,
      "count": null,
      "users": null
    },
    "policy": {
      "id": 6,
      "description": "Voting group",
      "user_groups": [
        ' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . '
      ]
    },
    "user_groups": [' . $userGroupsJson . ']
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
    "id": ' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID . ',
    "title": "Roll call",
    "status": "open",
    "current_time": ' . $jsonParsed[0]['current_time'] . ',
    "answers": [
      {
        "api_id": "present",
        "title": "Anwesend",
        "result": null
      }
    ],
    "has_majority": false,
    "is_presence_call": true,
    "publicity": {
      "single_votes": "admins",
      "results": "everybody"
    },
    "statistics": {
      "votes": 1,
      "voters": 1
    },
    "item_groups": [
      {
        "id": "single:question:1",
        "items": [
          {
            "type": "question",
            "id": 1
          }
        ],
        "name": null,
        "results": {
          "counts": [
            {
              "answers": [
                {
                  "answer": "present",
                  "votes": 7
                }
              ],
              "organization": null
            }
          ],
          "quorum": null
        },
        "single_votes": [
          {
            "answer": "present",
            "weight": 7,
            "voter": {
              "user_id": 2,
              "user_group_ids": [
                ' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . '
              ],
              "user_name": "testuser@example.org"
            }
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
      "eligible": false,
      "vote_weight": 1,
      "abstained": false,
      "votes": [],
      "can_vote_group_ids": [],
      "votes_remaining": null
    },
    "settings": {
      "votes_public": 1,
      "results_public": 1,
      "votes_names": 0,
      "answers_template": 2,
      "majority_type": 1,
      "quorum_type": 0,
      "voting_time": null,
      "assigned_motion_id": null,
      "policy": {
        "id": 6,
        "description": "Voting group",
        "user_groups": [
          ' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . '
        ]
      },
      "max_votes_by_group": null
    },
    "log": [
      {
        "type": "opened",
        "date": "' . $jsonParsed[0]['log'][0]['date'] . '"
      }
    ],
    "editable": {
      "items_can_be_added": false,
      "items_can_be_removed": false,
      "settings_can_be_changed": false
    },
    "assigned_motion_id": null,
    "opened_at": ' . $jsonParsed[0]['opened_at'] . ',
    "voting_time": null,
    "quorum": null,
    "abstention": {
      "enabled": false,
      "count": null,
      "users": null
    },
    "policy": {
      "id": 6,
      "description": "Voting group",
      "user_groups": [
        ' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . '
      ]
    },
    "user_groups": [' . $userGroupsJson . '],
    "eligibility": [
      {
        "group_id": ' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . ',
        "title": "Voting group",
        "users": [
          {
            "user_id": 2,
            "user_name": "testuser@example.org",
            "weight": 7
          }
        ]
      }
    ]
  },
  {
    "id": 1,
    "title": "\u00c42 or \u00c43",
    "status": "offline",
    "current_time": ' . $jsonParsed[1]['current_time'] . ',
    "answers": [
      {
        "api_id": "yes",
        "title": "Ja",
        "result": "accepted"
      },
      {
        "api_id": "no",
        "title": "Nein",
        "result": "rejected"
      },
      {
        "api_id": "abstention",
        "title": "Enthaltung",
        "result": null
      }
    ],
    "has_majority": true,
    "is_presence_call": false,
    "publicity": {
      "single_votes": "nobody",
      "results": "everybody"
    },
    "statistics": {
      "votes": 0,
      "voters": 0
    },
    "item_groups": [
      {
        "id": "single:amendment:3",
        "items": [
          {
            "type": "amendment",
            "id": 3
          }
        ],
        "name": null,
        "results": {
          "counts": [
            {
              "answers": [
                {
                  "answer": "yes",
                  "votes": 0
                },
                {
                  "answer": "no",
                  "votes": 0
                },
                {
                  "answer": "abstention",
                  "votes": 0
                }
              ],
              "organization": null
            }
          ],
          "quorum": null
        },
        "single_votes": null
      },
      {
        "id": "single:amendment:270",
        "items": [
          {
            "type": "amendment",
            "id": 270
          }
        ],
        "name": null,
        "results": {
          "counts": [
            {
              "answers": [
                {
                  "answer": "yes",
                  "votes": 0
                },
                {
                  "answer": "no",
                  "votes": 0
                },
                {
                  "answer": "abstention",
                  "votes": 0
                }
              ],
              "organization": null
            }
          ],
          "quorum": null
        },
        "single_votes": null
      },
      {
        "id": "single:amendment:274",
        "items": [
          {
            "type": "amendment",
            "id": 274
          }
        ],
        "name": null,
        "results": {
          "counts": [
            {
              "answers": [
                {
                  "answer": "yes",
                  "votes": 0
                },
                {
                  "answer": "no",
                  "votes": 0
                },
                {
                  "answer": "abstention",
                  "votes": 0
                }
              ],
              "organization": null
            }
          ],
          "quorum": null
        },
        "single_votes": null
      }
    ],
    "items": [
      {
        "type": "amendment",
        "id": 3,
        "group_id": "single:amendment:3",
        "title_with_prefix": "\u00c42 zu A2: O\u2019zapft is!",
        "prefix": "\u00c42",
        "initiators_html": "Testadmin",
        "url_html": "http://test.antragsgruen.test/stdparteitag/std-parteitag/motion/321-o-zapft-is/amendment/3",
        "url_json": "http://test.antragsgruen.test/stdparteitag/rest/std-parteitag/motion/321-o-zapft-is/amendment/3",
        "procedure_html": "<p>Abstimmung</p>",
        "result": null
      },
      {
        "type": "amendment",
        "id": 270,
        "group_id": "single:amendment:270",
        "title_with_prefix": "\u00c43 zu A2: O\u2019zapft is!",
        "prefix": "\u00c43",
        "initiators_html": "Tester",
        "url_html": "http://test.antragsgruen.test/stdparteitag/std-parteitag/motion/321-o-zapft-is/amendment/270",
        "url_json": "http://test.antragsgruen.test/stdparteitag/rest/std-parteitag/motion/321-o-zapft-is/amendment/270",
        "procedure_html": "<p>Abstimmung</p>",
        "result": null
      },
      {
        "type": "amendment",
        "id": 274,
        "group_id": "single:amendment:274",
        "title_with_prefix": "\u00c46 zu A2: O\u2019zapft is!",
        "prefix": "\u00c46",
        "initiators_html": "Tester",
        "url_html": "http://test.antragsgruen.test/stdparteitag/std-parteitag/motion/321-o-zapft-is/amendment/274",
        "url_json": "http://test.antragsgruen.test/stdparteitag/rest/std-parteitag/motion/321-o-zapft-is/amendment/274",
        "procedure_html": "<p>Erledigt durch: <a href=\"/stdparteitag/std-parteitag/motion/321-o-zapft-is/amendment/270\">\u00c43 zu A2</a></p>",
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
    "settings": {
      "votes_public": 0,
      "results_public": 1,
      "votes_names": 0,
      "answers_template": 0,
      "majority_type": null,
      "quorum_type": null,
      "voting_time": null,
      "assigned_motion_id": null,
      "policy": {
        "id": 2,
        "description": "Eingeloggte",
        "user_groups": null
      },
      "max_votes_by_group": null
    },
    "log": [],
    "editable": {
      "items_can_be_added": true,
      "items_can_be_removed": true,
      "settings_can_be_changed": true
    },
    "assigned_motion_id": null,
    "opened_at": null,
    "voting_time": null,
    "quorum": null,
    "abstention": {
      "enabled": false,
      "count": null,
      "users": null
    },
    "policy": {
      "id": 2,
      "description": "Eingeloggte",
      "user_groups": null
    },
    "user_groups": [' . $userGroupsJson . '],
    "eligibility": null
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
    "id": ' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID . ',
    "title": "Roll call",
    "status": "closed_published",
    "current_time": ' . $jsonParsed[0]['current_time'] . ',
    "answers": [
      {
        "api_id": "present",
        "title": "Anwesend",
        "result": null
      }
    ],
    "has_majority": false,
    "is_presence_call": true,
    "publicity": {
      "single_votes": "admins",
      "results": "everybody"
    },
    "statistics": {
      "votes": 1,
      "voters": 1
    },
    "item_groups": [
      {
        "id": "single:question:1",
        "items": [
          {
            "type": "question",
            "id": 1
          }
        ],
        "name": null,
        "results": {
          "counts": [
            {
              "answers": [
                {
                  "answer": "present",
                  "votes": 7
                }
              ],
              "organization": null
            }
          ],
          "quorum": null
        },
        "single_votes": null
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
      "eligible": false,
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
    "abstention": {
      "enabled": false,
      "count": null,
      "users": null
    },
    "policy": {
      "id": 6,
      "description": "Voting group",
      "user_groups": [
        ' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . '
      ]
    },
    "user_groups": [' . $userGroupsJson . ']
  }
]', $json);
