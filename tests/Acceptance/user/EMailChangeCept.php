<?php

/** @var \Codeception\Scenario $scenario */
use Tests\_pages\EmailChangePage;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->wantTo('login as a user without e-mail');
$I->click('#loginLink');
$I->see('LOGIN', 'h1');
$I->fillField('#username', 'noemail@example.org');
$I->fillField('#passwordInput', 'testuser');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');
$I->see('Willkommen!');


$I->wantTo('set an email');
$I->click('#myAccountLink');
$I->see('Neue E-Mail-Adresse:');
$I->dontSeeElement('.emailExistingRow');
$I->seeElement('.emailChangeRow');
$I->fillField('#userEmail', 'noemail@example.org');
$I->submitForm('.userAccountForm', [], 'save');
$I->see('an die angegebene Adresse geschickt', '.alert-success');
$I->see('E-mail sent to: noemail@example.org');
$I->seeElement('.changeRequested');

$I->openPage(EmailChangePage::class, [
    'subdomain'        => 'parteitag',
    'consultationPath' => 'parteitag',
    'email'            => 'kjkjh@example.org',
    'code'             => 'bla',
]);
$I->see('Diese E-Mail-Änderung wurde nicht beantragt oder bereits durchgeführt.');

$I->openPage(EmailChangePage::class, [
    'subdomain'        => 'parteitag',
    'consultationPath' => 'parteitag',
    'email'            => 'noemail@example.org',
    'code'             => 'bla',
]);
$I->see('Der angegebene Code stimmt leider nicht.');


$I->wantTo('resend the code');
$I->seeElement('.resendButton');
$I->submitForm('.userAccountForm', [], 'resendEmailChange');
$I->see('5 Minuten', '.alert-danger');


$I->wantTo('confirm the previous mail');

$I->openPage(EmailChangePage::class, [
    'subdomain'        => 'parteitag',
    'consultationPath' => 'parteitag',
    'email'            => 'noemail@example.org',
    'code'             => 'testCode',
]);
$I->see('Die E-Mail-Adresse wurde wie gewünscht geändert.');
$I->dontSee('Neue E-Mail-Adresse:');
$I->seeElement('.emailExistingRow');
$I->dontSeeElement('.emailChangeRow');



$I->wantTo('change it again');
$I->dontSee('Neue E-Mail-Adresse:');
$I->seeElement('.emailExistingRow');
$I->dontSeeElement('.changeRequested');
$I->dontSeeElement('.emailChangeRow');

$I->click('.requestEmailChange');
$I->dontSeeElement('.emailExistingRow');
$I->seeElement('.emailChangeRow');
$I->fillField('#userEmail', 'noemail2@example.org');
$I->submitForm('.userAccountForm', [], 'save');
$I->see('an die angegebene Adresse geschickt', '.alert-success');
$I->see('E-mail sent to: noemail2@example.org');
$I->see('noemail2@example.org', '.changeRequested');

$I->openPage(EmailChangePage::class, [
    'subdomain'        => 'parteitag',
    'consultationPath' => 'parteitag',
    'email'            => 'noemail2@example.org',
    'code'             => 'testCode',
]);
$I->see('Die E-Mail-Adresse wurde wie gewünscht geändert.');
$I->dontSee('Neue E-Mail-Adresse:');
$I->see('noemail2@example.org', '.emailExistingRow');
$I->dontSee('noemail@example.org');
$I->dontSeeElement('.changeRequested');
$I->dontSeeElement('.emailChangeRow');
