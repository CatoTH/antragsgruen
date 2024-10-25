<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->loginAndGotoStdAdminPage()->gotoUserAdministration();

$I->wantTo('not be able to edit users as regular admin');
$I->dontSeeElement('.editUserModal');
$I->clickJS('.user7 .btnEdit');
$I->wait(0.5);
$I->seeElement('.editUserModal .onlyGlobalAdminsHint');
$I->dontSeeElement('.editUserModal .inputNameGiven');
$I->dontSeeElement('.editUserModal .inputNameFamily');
$I->dontSeeElement('.editUserModal .inputOrganization');
$I->clickJS('.editUserModal .btnCancel');
$I->wait(0.3);
$I->logout();


$I->wantTo('be able to edit users as global admin');
$I->loginAsGlobalAdmin();
$I->gotoStdAdminPage()->gotoUserAdministration();
$I->clickJS('.user7 .btnEdit');
$I->wait(0.5);
$I->dontSeeElement('.editUserModal .onlyGlobalAdminsHint');
$I->seeElement('.editUserModal .inputNameGiven');
$I->seeElement('.editUserModal .inputNameFamily');
$I->seeElement('.editUserModal .inputOrganization');

$I->fillField('.editUserModal .inputNameGiven', 'Sincon');
$I->fillField('.editUserModal .inputNameFamily', 'Anö');
$I->fillField('.editUserModal .inputOrganization', 'Testorga');
$I->clickJS('.editUserModal .btnSave');
$I->wait(0.3);

$I->see('Sincon Anö', '.user7');
$I->see('Testorga', '.user7');

$I->wantTo('change their password');
$I->clickJS('.user7 .btnEdit');
$I->wait(0.5);
$I->dontSeeElement('.editUserModal .inputPassword');
$I->clickJS('.editUserModal .btnSetPwdOpener');
$I->wait(0.1);
$I->seeElement('.editUserModal .inputPassword');
$I->fillField('.editUserModal .inputPassword', 'GreatSecretPassword');
$I->clickJS('.editUserModal .btnSave');
$I->wait(0.3);


$I->wantTo('confirm the changes as the user');
$I->gotoConsultationHome();
$I->logout();
$I->loginAsConsultationAdmin();
$I->seeElement('.passwordError');
$I->fillField('#passwordInput', 'GreatSecretPassword');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');
$I->click('#myAccountLink');
$I->see('Sincon', '.userAccountForm');
$I->see('Anö', '.userAccountForm');
