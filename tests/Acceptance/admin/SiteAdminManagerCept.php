<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('Login as regular user');
$I->gotoConsultationHome();
$I->loginAsStdUser();
$I->dontSee('Einstellungen', '#adminLink');

$I->wantTo('Logout again');
$I->logout();


$I->wantTo('Add testuser as admin using the batch-creation mode');

$I->loginAndGotoStdAdminPage()->gotoUserAdministration();

$I->dontSeeElement('.user2');
$I->clickJS('.addUsersOpener.email');
$I->fillField('#emailAddresses', 'testuser@example.org');
$I->fillField('#names', 'ignored');
$I->submitForm('.addUsersByLogin.multiuser', [], 'addUsers');

$I->wait(1);
$I->seeElement('.user2');
$I->clickJS('.user2 .btnEdit');
$I->wait(0.5);
$I->seeCheckboxIsChecked('.editUserModal .userGroup4 input');
$I->uncheckOption('.editUserModal .userGroup4 input');
$I->checkOption('.editUserModal .userGroup1 input');
$I->clickJS('.editUserModal .btnSave');
$I->wait(0.3);
$I->see('Seiten-Admin', '.user2');


$I->wantTo('Login in as testuser');
$I->logout();
$I->gotoConsultationHome();
$I->loginAsStdUser();
$I->see('Einstellungen', '#adminLink');


$I->wantTo('Remove testadmin as admin');
$I->gotoStdAdminPage()->gotoUserAdministration();

$I->see('testadmin@example.org');
$I->clickJS('.userAdminList .user1 .btnRemove');
$I->wait(1);
$I->seeBootboxDialog('Testadmin wirklich aus der Liste entfernen?');
$I->acceptBootboxConfirm();
$I->wait(1);
$I->dontSee('testadmin@example.org');


$I->wantTo('Login in as testadmin');
$I->logout();
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->dontsee('Einstellungen', '#adminLink');
