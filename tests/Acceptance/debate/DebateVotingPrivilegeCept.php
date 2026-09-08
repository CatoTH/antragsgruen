<?php

/** @var \Codeception\Scenario $scenario */

use app\models\settings\Privileges;
use Tests\Support\AcceptanceTester;

// Saying which voting belongs to the debated item is part of moderating a debate; administering that
// voting is not, and takes the privilege to manage votings. A moderator without it is shown the
// voting, and is given none of its controls.

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$tabVoting = '.currentDebateAdmin .debateAdminTabs .tab:nth-of-type(3)';

$I->wantTo('create a group that may moderate debates but not administer votings');
$I->loginAndGotoStdAdminPage()->gotoUserAdministration();
$I->clickJS('.btnGroupCreate');
$I->fillField('.addGroupForm .addGroupName input', 'Debate moderators');
$I->clickJS('.addGroupForm .btnSave');
$I->wait(0.5);
$I->clickJS('.group' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . ' .btnEdit');
$I->wait(0.5);
$I->clickJS('.editGroupModal .privilege' . Privileges::PRIVILEGE_DEBATE_MODERATION . ' input');
$I->clickJS('.editGroupModal .btnSave');
$I->wait(0.5);

$I->wantTo('put testuser into that group');
// testuser is not a member of this consultation yet, so they are invited first
$I->dontSeeElement('.user2');
$I->clickJS('.addUsersOpener.email');
$I->fillField('#emailAddresses', 'testuser@example.org');
$I->fillField('#names', 'Testuser');
$I->submitForm('.addUsersByLogin.multiuser', [], 'addUsers');
$I->seeElement('.alert-success');
$I->wait(0.3);

$I->clickJS('.user2 .btnEdit');
$I->wait(0.5);
$I->seeElement('.editUserModal');
$I->clickJS('.editUserModal .userGroup' . AcceptanceTester::FIRST_FREE_USERGROUP_ID);
$I->clickJS('.editUserModal .btnSave');
$I->wait(0.5);
$I->see('Debate moderators', '.user2');

$I->wantTo('assign a voting to the debated item, as someone who may');
$I->gotoConsultationHome();
$I->click($tabVoting);
$I->waitForElement('.currentDebateAdmin .votingTab .votingCreate', 8);
$I->selectOption('.votingTab #debateVotingSelectExisting', 'Ä2 or Ä3');
$I->wait(0.2); // let Vue enable the "assign" button
$I->click('.votingTab .votingCreate .votingAssignRow button');
// Holding the privilege, this admin administers it in place
$I->waitForElement('.currentDebateAdmin .votingTab .embeddedVotingAdmin .voting', 8);

$I->wantTo('see the voting, but none of its controls, as a moderator who may not administer it');
$I->logout();
$I->loginAsStdUser();
$I->gotoConsultationHome();
$I->waitForElement('.currentDebateAdmin .debatedItem', 5);
$I->click($tabVoting);
$I->waitForElement('.currentDebateAdmin .votingTab .votingCard', 8);
$I->see('Ä2 or Ä3', '.votingTab .votingCard .votingCardTitle');
// Neither the administration itself nor the link to the page carrying it - which they could not open
$I->dontSeeElement('.votingTab .embeddedVotingAdmin');
$I->dontSee('Abstimmung verwalten', '.votingTab .votingCard');
// Choosing which voting is being debated is theirs, though
$I->seeElement('.votingTab .votingCard .votingAssignRow button');
