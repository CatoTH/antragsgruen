<?php

/** @var \Codeception\Scenario $scenario */
use app\models\policies\IPolicy;
use app\models\quorumType\IQuorumType;
use app\models\votings\AnswerTemplates;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('create a user group with voting rights and add three users to it');
$I->loginAndGotoStdAdminPage()->gotoUserAdministration();
$I->clickJS('.btnGroupCreate');
$I->fillField('.addGroupName input', 'Voting rights');
$I->clickJS('.addGroupForm .btnSave');
$I->wait(0.3);

$I->see('Voting rights', '.groupList .group' . AcceptanceTester::FIRST_FREE_USERGROUP_ID);

$I->dontSeeElement('.user2');
$I->clickJS('.addUsersOpener.email');
$I->fillField('#emailAddresses', 'testuser@example.org');
$I->fillField('#names', 'Testuser');
$I->submitForm('.addUsersByLogin.multiuser', [], 'addUsers');
$I->seeElement('.alert-success');

$I->wait(0.3);

// testadmin@example.org, testuser@example.org, consultationadmin@example.org
foreach (['1', '2', '7'] as $userId) {
    $I->seeElement('.user' . $userId);
    $I->clickJS('.user' . $userId . ' .btnEdit');
    $I->wait(0.5);
    $I->seeElement('.editUserModal');
    $I->clickJS('.editUserModal .userGroup' . AcceptanceTester::FIRST_FREE_USERGROUP_ID);
    $I->clickJS('.editUserModal .btnSave');
    $I->wait(0.5);
    $I->see('Voting rights', '.user' . $userId);
}


$I->wantTo('open a roll call with quorum');
$I->gotoStdAdminPage()->gotoVotingPage();
$I->clickJS('.createVotingOpener');
$I->seeElement('form.creatingVoting');

$I->assertSame('question', $I->executeJS('return $("input[name=votingTypeNew]:checked").val()'));
$I->fillField('.creatingVoting .settingsTitle', 'Roll call');
$I->fillField('.creatingVoting .settingsQuestion', 'Who is present?');

$I->clickJS("input[name=answersNew][value='" . AnswerTemplates::TEMPLATE_PRESENT . "']");
$I->dontSeeElement('.createVotingHolder .userGroupSelectList');
$I->selectOption('.createVotingHolder .policySelect', IPolicy::POLICY_USER_GROUPS);
$I->wait(0.1);
$I->seeElement('.createVotingHolder .userGroupSelectList');
$I->executeJS('document.querySelector(".createVotingHolder select.userGroupSelectList").selectize.addItem(' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . ')');

$I->assertSame(1, (int)$I->executeJS('return $("input[name=resultsPublicNew]:checked").val()'));
$I->clickJS('input[name=votesPublicNew][value=\"2\"]');
$I->clickJS('input[name=resultsPublicNew][value=\"1\"]');
$I->clickJS('form.creatingVoting button[type=submit]');
$I->wait(0.3);

$votingId = '#voting' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID;
$I->seeElement($votingId);
$I->see('Roll call', $votingId . ' h2');
$I->clickJS($votingId . ' .settingsToggleGroup .dropdown-toggle');
$I->seeElement($votingId . ' .votingSettings .selectize-control');
$I->seeElement($votingId . ' .quorumTypeSettings');
$I->clickJS($votingId . ' .quorumTypeSettings input[value=\"' . IQuorumType::QUORUM_TYPE_HALF . '\"]');
$I->clickJS($votingId . ' .btnSave');
$I->wait(0.3);
$I->see('Einfache Mehrheit (2 von 3 Berechtigten)', $votingId . ' .quorumType');


$I->wantTo('open it and vote once (missing the quorum)');
$I->clickJS($votingId . ' .btnOpen');
$I->wait(0.3);
$I->see('Quorum: 0 von 2 nötigen Stimmen', $votingId . ' .quorumCounter');

$I->gotoConsultationHome();
$I->see('Alle Eingeloggte können die abgegebenen Stimmen einsehen.', '.voting');
$I->clickJS('.voting_question_1 .btnPresent');
$I->wait(0.2);
$I->seeElement('.voting_question_1 .voted .present');

$I->gotoStdAdminPage()->gotoVotingPage();
$I->see('Quorum: 1 von 2 nötigen Stimmen', $votingId . ' .quorumCounter');
$I->clickJS($votingId . ' .btnClose');
$I->wait(0.3);
$I->see('Quorum verfehlt', '.voting_question_1 .rejected');


$I->wantTo('Open again, have a second vote and pass the quorum this time');
$I->clickJS($votingId . ' .btnReopen');
$I->gotoConsultationHome();
$I->logout();
$I->loginAsStdUser();
$I->wait(0.2);
$I->clickJS('.voting_question_1 .btnPresent');
$I->wait(0.2);
$I->seeElement('.voting_question_1 .voted .present');

$I->logout();
$I->loginAsStdAdmin();
$I->gotoStdAdminPage()->gotoVotingPage();
$I->see('Quorum: 2 von 2 nötigen Stimmen', $votingId . ' .quorumCounter');
$I->clickJS($votingId . ' .btnClose');
$I->wait(0.3);
$I->see('Quorum erreicht', '.voting_question_1 .accepted');
$I->see('2', '.voting_question_1 .voteCount_present');
$I->clickJS('.voting_question_1 .btnShowVotes');
$I->see('testadmin@example.org', '.voteResults');
$I->see('testuser@example.org', '.voteResults');
