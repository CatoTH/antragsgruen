<?php

/** @var \Codeception\Scenario $scenario */

use OTPHP\TOTP;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();
$I->gotoConsultationHome();

$I->wantTo('be able to enforce password-change as admin');
$I->loginAsGlobalAdmin();
$I->gotoStdAdminPage()->gotoUserAdministration();
$I->clickJS('.user1 .btnEdit');
$I->wait(0.5);
$I->dontSeeCheckboxIsChecked('.forcePwdChangeHolder input');
$I->clickJS('.forcePwdChangeHolder input');
$I->seeCheckboxIsChecked('.forcePwdChangeHolder input');
$I->clickJS('.editUserModal .btnSave');
$I->wait(0.3);


$I->wantTo('be forced to set up a second factor');
$I->logout();
$I->click('#loginLink');

$I->see('LOGIN', 'h1');
$I->fillField('#username', 'testadmin@example.org');
$I->fillField('#passwordInput', 'testadmin');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');

$I->seeElement('.forcedPwdForm');
$I->dontSeeElement('.alert-danger');
$I->fillField("//input[@name='pwd']", 'MyNewPassword');
$I->fillField("//input[@name='pwd2']", 'MyIncorrectPassword');
$I->submitForm('.forcedPwdForm', [], 'change');

$I->seeElement('.forcedPwdForm');
$I->seeElement('.alert-danger');
$I->fillField("//input[@name='pwd']", 'MyNewPassword');
$I->fillField("//input[@name='pwd2']", 'MyNewPassword');
$I->submitForm('.forcedPwdForm', [], 'change');

$I->seeElement('.alert-success');


$I->wantTo('not have to do this again on the next login');
$I->logout();
$I->click('#loginLink');

$I->see('LOGIN', 'h1');
$I->fillField('#username', 'testadmin@example.org');
$I->fillField('#passwordInput', 'MyNewPassword');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');

$I->dontSeeElement('.forcedPwdForm');
$I->seeElement('.alert-success');
