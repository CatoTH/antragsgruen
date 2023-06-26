<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('check the basic configuration');
$page = $I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->checkOption('.managedUserAccounts input');
$page->saveForm();

$I->gotoStdAdminPage()->gotoUserAdministration();

$I->clickJS('.addUsersOpener.email');
$I->wait(0.1);
$I->see('Benachrichtigungs-E-Mail', '.alert-info');
$I->dontSee('Datenschutzgründen', '.alert-info');
$I->dontSeeElement('#passwords');


$I->wantTo('disable e-mails');

$I->setAntragsgruenConfiguration(['mailService' => ['transport' => 'none']]);

$I->gotoStdAdminPage()->gotoUserAdministration();

$I->wantTo('create a user using the old batch-creation mode');

$I->dontSeeElement('#emailAddresses');
$I->clickJS('.addUsersOpener.email');
$I->dontSee('Benachrichtigungs-E-Mail', '.alert-info');
$I->see('Datenschutzgründen', '.alert-info');

$I->seeElement('#emailAddresses');
$I->seeElement('#passwords');

$I->fillField('#emailAddresses', 'blibla@example.org');
$I->fillField('#passwords', 'bliblablubb');
$I->fillField('#names', 'Kasper');
$I->submitForm('.addUsersByLogin.multiuser', [], 'addUsers');

$I->wait(0.5);
$I->see('Kasper', '.userAdminList');
$I->see('blibla@example.org', '.userAdminList');



$I->wantTo('log in with the new user');
$I->gotoConsultationHome();
$I->logout();
$I->click('#loginLink');
$I->fillField('#username', 'blibla@example.org');
$I->fillField('#passwordInput', 'bliblablubb');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');
$I->see('Willkommen!', '.alert-success');
