<?php

/** @var \Codeception\Scenario $scenario */

use OTPHP\TOTP;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();
$I->gotoConsultationHome();

$I->wantTo('be able to enforce TOTP as super-admin');
$I->loginAsGlobalAdmin();
$I->gotoStdAdminPage()->gotoUserAdministration();
$I->clickJS('.user1 .btnEdit');
$I->wait(0.5);
$I->dontSeeCheckboxIsChecked('.force2FaHolder input');
$I->clickJS('.force2FaHolder input');
$I->seeCheckboxIsChecked('.force2FaHolder input');
$I->clickJS('.editUserModal .btnSave');
$I->wait(0.3);


$I->wantTo('be forced to set up a second factor');
$I->logout();
$I->click('#loginLink');

$I->see('LOGIN', 'h1');
$I->fillField('#username', 'testadmin@example.org');
$I->fillField('#passwordInput', 'testadmin');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');

$I->seeElement('.forcedTfaForm');
$src = $I->executeJS('return document.querySelector(".tfaqr").src');
$I->assertStringContainsString('data:image/png;base64,', $src);

$otp = TOTP::createFromSecret(trim((string) file_get_contents(__DIR__ . '/../../config/2fa.secret')));

$correct2fa = $otp->now();
$I->fillField("//input[@name='set2fa']", $correct2fa);
$I->submitForm('.forcedTfaForm', []);

$I->seeElement('.alert-success');


$I->wantTo('disable it again, but cannot');

$I->click('#myAccountLink');
$I->seeElement('.tfaRow .glyphicon-ok');
$I->dontSeeElement('.btn2FaRemoveOpen');
