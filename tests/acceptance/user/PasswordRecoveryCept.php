<?php

/** @var \Codeception\Scenario $scenario */
use app\models\db\EMailLog;
use app\models\db\User;
use app\tests\_pages\LoginPage;
use app\tests\_pages\PasswordRecoveryPage;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('go to the recovery page');

$I->wantTo('Load the login page');
LoginPage::openBy(
    $I,
    [
        'subdomain'        => 'stdparteitag',
        'consultationPath' => 'std-parteitag',
    ]
);
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
PasswordRecoveryPage::openBy(
    $I,
    [
        'subdomain'        => 'stdparteitag',
        'consultationPath' => 'std-parteitag',
        'email'            => 'testuser@example.org',
        'code'             => 'test',
    ]
);
$I->fillField('#sendEmail', 'testuser@example.org');
$I->submitForm('.sendConfirmationForm', [], 'send');

$I->see('Es wurde bereits eine Wiederherstellungs-E-Mail in den letzten 24 Stunden verschickt.');



$I->wantTo('confirm the e-mail');

/** @var User $user */
$user                = User::findOne(['id' => 2]);
$user->recoveryAt    = date('Y-m-d H:i:s');
$user->recoveryToken = password_hash('test', PASSWORD_DEFAULT);
$user->save();

PasswordRecoveryPage::openBy(
    $I,
    [
        'subdomain'        => 'stdparteitag',
        'consultationPath' => 'std-parteitag',
        'email'            => 'testuser@example.org',
        'code'             => 'test',
    ]
);

$I->seeInField('#recoveryEmail', 'testuser@example.org');
$I->seeInField('#recoveryCode', 'test');
$I->fillField('#recoveryPassword', 'test2');
$I->submitForm('.resetPasswortForm', [], 'recover');

$I->see('Alles klar! Dein Passwort wurde geändert.');



$I->wantTo('confirm the e-mail again');
PasswordRecoveryPage::openBy(
    $I,
    [
        'subdomain'        => 'stdparteitag',
        'consultationPath' => 'std-parteitag',
        'email'            => 'testuser@example.org',
        'code'             => 'test',
    ]
);
$I->fillField('#recoveryPassword', 'test2');
$I->submitForm('.resetPasswortForm', [], 'recover');
$I->see('Es wurde kein Wiederherstellungs-Antrag innerhalb der letzten 24 Stunden gestellt.');

