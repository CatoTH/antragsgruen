<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->loginAsConsultationAdmin();
$I->gotoStdAdminPage()->gotoUserAdministration();

$I->wantTo('Remove admin permissions from the site admin');
$I->clickJS('.user1 .btnEdit');
$I->wait(0.5);
$I->seeElement('.editUserModal');
$I->clickJS('.editUserModal .userGroup1');
$I->clickJS('.editUserModal .btnSave');
$I->wait(0.5);
$I->seeBootboxDialog('Nur Seiten-Administrator*innen selbst können ebendiese Gruppe verwalten');
$I->acceptBootboxConfirm();

$I->clickJS('.user1 .btnRemove');
$I->seeBootboxDialog('Testadmin wirklich aus der Liste entfernen?');
$I->acceptBootboxConfirm();
$I->seeBootboxDialog('Nur Seiten-Administrator*innen selbst können ebendiese Gruppe verwalten');
$I->acceptBootboxConfirm();


$I->wantTo('Give myself higher privileges');
$I->clickJS('.user7 .btnEdit');
$I->wait(0.5);
$I->seeElement('.editUserModal');
$I->clickJS('.editUserModal .userGroup1');
$I->clickJS('.editUserModal .btnSave');
$I->wait(0.5);
$I->seeBootboxDialog('Nur Seiten-Administrator*innen selbst können ebendiese Gruppe verwalten');
$I->acceptBootboxConfirm();


$I->wantTo('lock myself out');
$I->clickJS('.user7 .btnEdit');
$I->clickJS('.editUserModal .userGroup1');
$I->clickJS('.editUserModal .userGroup2');
$I->wait(0.5);
$I->clickJS('.editUserModal .btnSave');
$I->wait(0.5);
$I->seeBootboxDialog('Es ist nicht möglich, sich selbst die Rechte zu dieser Seite zu entziehen');
$I->acceptBootboxConfirm();

$I->clickJS('.user7 .btnRemove');
$I->seeBootboxDialog('Single-Consultation Admin wirklich aus der Liste entfernen?');
$I->acceptBootboxConfirm();
$I->seeBootboxDialog('Es ist nicht möglich, sich selbst die Rechte zu dieser Seite zu entziehen');
$I->acceptBootboxConfirm();
