<?php

/** @var \Codeception\Scenario $scenario */

use app\models\policies\IPolicy;

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


$I->wantTo('restrict creating amendments to the new user group');
$page = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->dontSeeElement('.policyWidgetAmendments .userGroupSelect');
$I->selectOption('#typePolicyAmendments', IPolicy::POLICY_USER_GROUPS);
$I->wait(0.1);
$I->seeElement('.policyWidgetAmendments .userGroupSelect');
$I->assertSame(0, $I->executeJS('return document.querySelector("#typePolicyAmendmentsGroups").selectize.items.length'));
$I->executeJS('document.querySelector("#typePolicyAmendmentsGroups").selectize.addItem(' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . ')');
$I->assertSame(1, $I->executeJS('return document.querySelector("#typePolicyAmendmentsGroups").selectize.items.length'));
$page->saveForm();
$I->wait(0.1);
$I->see('Special group', '.policyWidgetAmendments .selectize-input');


$I->wantTo('not being able to create amendments as a user');
$I->logout();
$I->gotoMotion();
$I->loginAsStdUser();
$I->see('Nur zugelassene Gruppen können Änderungsanträge stellen', '#sidebar');


$I->wantTo('assign the group to an user (Testuser)');
$I->logout();
$I->loginAndGotoStdAdminPage()->gotoUserAdministration();
$I->dontSeeElement('.user2');
$I->clickJS('.addUsersOpener.email');
$I->fillField('#emailAddresses', 'testuser@example.org');
$I->fillField('#names', 'ignored');
$I->submitForm('#accountsCreateForm', [], 'addUsers');
$I->wait(1);
$I->seeElement('.user2');
$I->dontSeeElement('.vs__dropdown-toggle');
$I->clickJS('.user2 .btnEdit');
$I->seeElement('.vs__dropdown-toggle');
$I->executeJS('userWidget.$refs["user-admin-widget"].setSelectedGroups([' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . '], { id: 2 });');
$I->executeJS('userWidget.$refs["user-admin-widget"].saveUser({id: 2});');
$I->wait(0.5);
$I->dontSee('Veranstaltungs-Admin', '.user2');
$I->dontSee('Teilnehmer*in', '.user2');
$I->see('Special group', '.user2');


$I->wantTo('be able to create amendments now as that user');
$I->logout();
$I->gotoMotion();
$I->loginAsStdUser();
$I->dontSee('Nur zugelassene Gruppen können Änderungsanträge stellen', '#sidebar');
$I->click('#sidebar .amendmentCreate a');
$I->see('Änderungsantrag stellen', '.breadcrumb');


$I->wantTo('delete the group again');
$I->logout();
$I->loginAndGotoStdAdminPage()->gotoUserAdministration();
$I->clickJS('.group' . AcceptanceTester::FIRST_FREE_USERGROUP_ID . ' .btnRemove');
$I->seeBootboxDialog('Special group wirklich löschen?');
$I->acceptBootboxConfirm();
$I->dontSee('Veranstaltungs-Admin', '.user2');
$I->see('Teilnehmer*in', '.user2');
$I->dontSee('Special group', '.user2');
