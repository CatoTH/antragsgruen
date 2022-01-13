<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('Create a user group');
$I->loginAndGotoStdAdminPage()->gotoUserAdministration();
$I->see('Teilnehmer*in', '.group4');
$I->dontSeeElement('.addGroupForm');
$I->clickJS('.btnGroupCreate');
$I->seeElement('.addGroupForm');
$I->fillField('.addGroupForm .addGroupName input', 'Special group');
$I->clickJS('.addGroupForm .btnSave');
$I->wait(0.5);
$I->see('Special group', '.group' . AcceptanceTester::FIRST_FREE_USERGROUP_ID);

$I->wantTo('assign the group to another user (Single-Consultation Admin)');
$I->clickJS('.user7 .btnEdit');
$I->see('Veranstaltungs-Admin', '.user7');
$I->executeJS('userWidget.$refs["user-admin-widget"].setSelectedGroups([' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . '], { id: 7 });');
$I->executeJS('userWidget.$refs["user-admin-widget"].saveUser({id: 7});');
$I->wait(0.5);
$I->dontSee('Veranstaltungs-Admin', '.user7');
$I->dontSee('Teilnehmer*in', '.user7');
$I->see('Special group', '.user7');

$I->wantTo('delete the group again');
$I->clickJS('.group' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . ' .btnRemove');
$I->seeBootboxDialog('Special group wirklich löschen?');
$I->acceptBootboxConfirm();
$I->dontSee('Veranstaltungs-Admin', '.user7');
$I->see('Teilnehmer*in', '.user7');
$I->dontSee('Special group', '.user7');
