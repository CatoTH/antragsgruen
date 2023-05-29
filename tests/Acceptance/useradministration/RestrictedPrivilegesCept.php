<?php

/** @var \Codeception\Scenario $scenario */

use app\models\settings\Privileges;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('Create a generic user group');
$I->loginAndGotoStdAdminPage()->gotoUserAdministration();
$I->clickJS('.btnGroupCreate');
$I->seeElement('.addGroupForm');
$I->fillField('.addGroupForm .addGroupName input', 'General group');
$I->clickJS('.addGroupForm .btnSave');
$I->wait(0.5);
$I->see('General group', '.group' . AcceptanceTester::FIRST_FREE_USERGROUP_ID);

$I->wantTo('Grant restricted permissions to this group: motion status edit permissions on "Umwelt" tag');
$I->clickJS('.group' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . ' .btnEdit');
$I->wait(0.5);
$I->see('General group', '.editGroupModal header');
$I->seeInField('.editGroupModal .inputGroupTitle', 'General group');
$I->fillField('.editGroupModal .inputGroupTitle', 'Restricted Group');
$I->dontSeeElement('.addRestrictedPermissionDialog');
$I->clickJS('.editGroupModal .btnAddRestrictedPermission');
$I->seeElement('.addRestrictedPermissionDialog');
$I->dontSeeElement('.editGroupModal .inputGroupTitle');
$I->dontSeeElement('.editGroupModal .restrictedTo .stdDropdown.tags');
$I->clickJS('.editGroupModal .restrictedPermissions .privilege' . Privileges::PRIVILEGE_MOTION_STATUS_EDIT . ' input');
$I->clickJS('.editGroupModal .restrictedTo .tag input');
$I->seeElement('.editGroupModal .restrictedTo .stdDropdown.tags');
$I->selectOption('.editGroupModal .restrictedTo .stdDropdown.tags', '1');
$I->clickJS('.editGroupModal .btnAdd');
$I->see('Rahmendaten bearbeiten', '.editGroupModal .restrictedPrivilegeList');
$I->see('Umwelt', '.editGroupModal .restrictedPrivilegeList');
$I->clickJS('.editGroupModal .btnSave');
$I->wait(0.2);
$I->see('Restricted Group', '.group' . AcceptanceTester::FIRST_FREE_USERGROUP_ID);
$I->see('Umwelt: Rahmendaten bearbeiten', '.group' . AcceptanceTester::FIRST_FREE_USERGROUP_ID);

$I->wantTo('Add the testuser to the newly created group');
$I->gotoStdAdminPage()->gotoUserAdministration(); // Reload page, as groups are not dynamically loaded in "adding form" yet
$I->fillField('.addSingleInit .inputEmail', 'testuser@example.org');
$I->clickJS('.addUsersOpener.singleuser');
$I->wait(0.5);
$I->seeElement('.addUsersByLogin.singleuser .showIfExists');
$I->dontSeeElement('.addUsersByLogin.singleuser .showIfNew');
$I->checkOption('.addUsersByLogin.singleuser .userGroup' . AcceptanceTester::FIRST_FREE_USERGROUP_ID);
$I->uncheckOption('.addUsersByLogin.singleuser .userGroup4');
$I->submitForm('.addUsersByLogin.singleuser', [], 'addUsers');
$I->wait(0.5);
$I->see('Restricted Group', '.user2');

$I->wantTo('Check the activity log for the user group');
$I->clickJS('.group' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . ' .btnEdit');
$I->wait(0.5);
$I->click('.editGroupModal .changeLogLink');
$I->see('testuser@example.org wurde der Gruppe „Restricted Group” hinzugefügt.');


$I->wantTo('Test the motion functionality as stduser');
$I->gotoConsultationHome();
$I->logout();
$I->loginAsStdUser();
$I->click('#motionListLink');
$I->dontSeeElement('.actionCol'); // No screening and no deletion permissions
$I->dontSeeElement('.motion2 .titleCol a');
$I->seeElement('.motion3 .titleCol a');
$I->click('.motion3 .titleCol a');
$I->fillField('#motionTitle', 'Formatted text');
$I->fillField('#motionNoteInternal', 'Some internal notes');
$I->dontSeeElement('#motionTextEditCaller');
$I->dontSeeElement('#motionTextEditHolder');
$I->dontSeeElement('.supporterForm');
$I->dontSeeElement('#motionSupporterHolder');
$I->submitForm('#motionUpdateForm', [], 'save');
$I->seeInField('#motionTitle', 'Formatted text');
$I->seeInField('#motionNoteInternal', 'Some internal notes');
// @TODO Test that no voting data can be changed
$I->click('#sidebar .view');
$I->see('Formatted text', 'h1');
$I->see('Testadmin', '.motionDataTable');
$I->see('unterstrichen', 'span.underline');


$I->wantTo('Test the amendment functionality as stduser');
$I->click('#motionListLink');
$I->dontSeeElement('.amendment1 .titleCol a');
$I->seeElement('.amendment2 .titleCol a');
$I->click('.amendment2 .titleCol a');
$I->fillField('#amendmentNoteInternal', 'Some internal notes');
$I->dontSeeElement('.amendmentTextEditCaller');
$I->dontSeeElement('.amendmentTextEditHolder');
$I->dontSeeElement('.supporterForm');
$I->dontSeeElement('#motionSupporterHolder');
$I->submitForm('#amendmentUpdateForm', [], 'save');
$I->seeInField('#amendmentNoteInternal', 'Some internal notes');
// @TODO Test that no voting data can be changed
$I->click('#sidebar .view');
$I->see('Testuser', '.motionDataTable');
$I->see('Und noch eine neue Zeile gq Q.', 'ins');
