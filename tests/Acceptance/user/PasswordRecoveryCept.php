<?php

/** @var \Codeception\Scenario $scenario */

use app\models\db\User;
use Tests\_pages\LoginPage;
use Tests\_pages\PasswordRecoveryPage;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('go to the recovery page');

$I->wantTo('Load the login page');
$I->openPage(LoginPage::class, [
    'subdomain'        => 'stdparteitag',
    'consultationPath' => 'std-parteitag',
]);
$I->see('Login', 'h1');
$I->click('#usernamePasswordForm .passwordRecovery a');


$I->wantTo('recover the password for a non-existant account');

$I->fillField('#sendEmail', 'invalid@example.org');
$I->submitForm('.sendConfirmationForm', [], 'send');
$I->see('Der Account invalid@example.org wurde nicht gefunden.');


$I->wantTo('recover the password');

$I->fillField('#sendEmail', 'testuser@example.org');
$I->submitForm('.sendConfirmationForm', [], 'send');

$I->see('Dir wurde eine Passwort-Wiederherstellungs-Mail geschickt.');


$I->wantTo('request another recovery');
$I->openPage(PasswordRecoveryPage::class, [
    'subdomain'        => 'stdparteitag',
    'consultationPath' => 'std-parteitag',
    'email'            => 'testuser@example.org',
    'code'             => 'test',
]);
$I->fillField('#sendEmail', 'testuser@example.org');
$I->submitForm('.sendConfirmationForm', [], 'send');

$I->see('Es wurde bereits eine Wiederherstellungs-E-Mail in den letzten 24 Stunden verschickt.');


$I->wantTo('confirm the e-mail');

/** @var User $user */
$user                = User::findOne(['id' => 2]);
$user->recoveryAt    = date('Y-m-d H:i:s');
$user->recoveryToken = password_hash('test', PASSWORD_DEFAULT);
$user->save();

$I->openPage(PasswordRecoveryPage::class, [
    'subdomain'        => 'stdparteitag',
    'consultationPath' => 'std-parteitag',
    'email'            => 'testuser@example.org',
    'code'             => 'test',
]);

$I->seeInField('#recoveryEmail', 'testuser@example.org');
$I->seeInField('#recoveryCode', 'test');
$I->fillField('#recoveryPassword', 'testpwd2');
$I->submitForm('.resetPasswortForm', [], 'recover');

$I->see('Alles klar! Dein Passwort wurde geändert.');


$I->wantTo('confirm the e-mail again');
$I->openPage(PasswordRecoveryPage::class, [
    'subdomain'        => 'stdparteitag',
    'consultationPath' => 'std-parteitag',
    'email'            => 'testuser@example.org',
    'code'             => 'test',
]);
$I->fillField('#recoveryPassword', 'testpwd2');
$I->submitForm('.resetPasswortForm', [], 'recover');
$I->see('Es wurde kein Wiederherstellungs-Antrag innerhalb der letzten 24 Stunden gestellt.');
