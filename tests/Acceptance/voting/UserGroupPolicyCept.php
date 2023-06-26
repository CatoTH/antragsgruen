<?php

/** @var \Codeception\Scenario $scenario */

use app\models\policies\IPolicy;
use app\models\policies\UserGroups;
use app\models\votings\AnswerTemplates;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


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
$I->clickJS('input[name=votesPublicNew][value=\"2\"]');
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


$I->wantTo('assign the group to a user (Testuser)');
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
$I->clickJS('.voting_question_1 .btnPresent');
$I->wait(0.3);
$I->seeElement('.voting_question_1 span.present');
