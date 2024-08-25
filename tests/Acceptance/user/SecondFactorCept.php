<?php

/** @var \Codeception\Scenario $scenario */

use app\models\db\User;
use OTPHP\TOTP;
use Tests\_pages\LoginPage;
use Tests\_pages\PasswordRecoveryPage;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('register a second factor');

$I->gotoConsultationHome();
$I->loginAsStdUser();
$I->click('#myAccountLink');
$I->seeElement('.tfaNotActive');
$I->dontSeeElement('.secondFactorAdderBody');
$I->clickJS('.btn2FaAdderOpen');
$I->seeElement('.secondFactorAdderBody');
$src = $I->executeJS('return document.querySelector(".tfaqr").src');
$I->assertStringContainsString('data:image/png;base64,', $src);

$otp = TOTP::createFromSecret(trim((string) file_get_contents(__DIR__ . '/../../config/2fa.secret')));

$correct2fa = $otp->now();
$I->fillField("//input[@name='set2fa']", $correct2fa);
$I->submitForm('.userAccountForm', [], 'save');
$I->seeElement('.tfaActive');


$I->logout();

$I->wantTo('login using an incorrect second factor');
$I->click('#loginLink');

$I->see('LOGIN', 'h1');
$I->fillField('#username', 'testuser@example.org');
$I->fillField('#passwordInput', 'testuser');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');
$I->seeElement('.tfaForm');
$I->fillField('//input[@name="2fa"]', '1234');
$I->submitForm('.tfaForm', [], null);
$I->seeElement('.tfaError');


$I->wantTo('login using a correct second factor');
$I->seeElement('.tfaForm');
$correct2fa = $otp->now();
$I->fillField('//input[@name="2fa"]', $correct2fa);
$I->submitForm('.tfaForm', [], null);
$I->seeElement('.alert-success');


$I->wantTo('remove the second factor again');
$I->click('#myAccountLink');
$I->seeElement('.tfaActive');
$I->clickJS('.btn2FaRemoveOpen');
$correct2fa = $otp->now();
$I->fillField('//input[@name="remove2fa"]', $correct2fa);
$I->submitForm('.userAccountForm', [], 'save');
$I->seeElement('.tfaNotActive');
$I->logout();


$I->wantTo('login without 2fa');
$I->loginAsStdUser();
