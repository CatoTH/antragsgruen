<?php

/** @var \Codeception\Scenario $scenario */

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->loginAsConsultationAdmin();
$I->gotoStdAdminPage()->gotoUserAdministration();
$I->wait(1);

$I->wantTo('Remove admin permissions from the site admin');
$I->clickJS('.user1 .btnEdit');
$I->seeElement('.vs__dropdown-toggle');
$I->executeJS('userWidget.$refs["user-admin-widget"].setSelectedGroups([], { id: 1 });');
$I->executeJS('userWidget.$refs["user-admin-widget"].saveUser({id: 1});');
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
$I->seeElement('.vs__dropdown-toggle');
$I->executeJS('userWidget.$refs["user-admin-widget"].setSelectedGroups([1, 2], { id: 7 });');
$I->executeJS('userWidget.$refs["user-admin-widget"].saveUser({id: 7});');
$I->wait(0.5);
$I->seeBootboxDialog('Nur Seiten-Administrator*innen selbst können ebendiese Gruppe verwalten');
$I->acceptBootboxConfirm();


$I->wantTo('lock myself out');
$I->clickJS('.user7 .btnEdit');
$I->seeElement('.vs__dropdown-toggle');
$I->executeJS('userWidget.$refs["user-admin-widget"].setSelectedGroups([], { id: 7 });');
$I->executeJS('userWidget.$refs["user-admin-widget"].saveUser({id: 7});');
$I->wait(0.5);
$I->seeBootboxDialog('Es ist nicht möglich, sich selbst die Rechte zu dieser Seite zu entziehen');
$I->acceptBootboxConfirm();

$I->clickJS('.user7 .btnRemove');
$I->seeBootboxDialog('Single-Consultation Admin wirklich aus der Liste entfernen?');
$I->acceptBootboxConfirm();
$I->seeBootboxDialog('Es ist nicht möglich, sich selbst die Rechte zu dieser Seite zu entziehen');
$I->acceptBootboxConfirm();
