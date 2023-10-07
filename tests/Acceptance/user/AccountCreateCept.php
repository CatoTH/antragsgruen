<?php

use Tests\_pages\LoginPage;
use Tests\Support\AcceptanceTester;

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();


// Load Form

$I->wantTo('Load the login page');
$I->openPage(LoginPage::class, [
    'subdomain'        => 'stdparteitag',
    'consultationPath' => 'std-parteitag',
]);
$I->see('Login', 'h1');


$I->wantTo('Create an account');

$I->fillField(['id' => 'username'], 'non_existant@example.org');
$I->fillField(['id' => 'passwordInput'], 'doesntmatter');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');
$I->see('Benutzer*innenname nicht gefunden');

$I->cantSee('Passwort (Bestätigung):');
$I->checkOption(['id' => 'createAccount']);
$I->see('Passwort (Bestätigung):');

$I->fillField(['id' => 'username'], 'testaccount@example.org');
$I->fillField(['id' => 'name'], 'Tester');

$I->fillField('#passwordInput', 'n');
$I->fillField('#passwordConfirm', 'n');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');
$I->seeBootboxDialog('Das Passwort muss mindestens 8 Zeichen lang sein.');
$I->acceptBootboxAlert();

$I->fillField('#passwordInput', 'newuser');
$I->fillField('#passwordConfirm', 'newuser2');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');
$I->seeBootboxDialog('Die beiden Passwörter stimmen nicht überein');
$I->acceptBootboxAlert();


$I->fillField(['id' => 'passwordInput'], 'testpassword');
$I->fillField(['id' => 'passwordConfirm'], 'testpassword');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');
$I->see(mb_strtoupper('Zugang bestätigen'), 'h1');


$I->wantTo('Confirm the account with a wrong code');

$I->fillField(['id' => 'code'], 'somethingcompletelywrong');
$I->submitForm('#confirmAccountForm', []);


$I->see(mb_strtoupper('Zugang bestätigen'), 'h1');
$I->see('Der angegebene Code stimmt leider nicht.');


$I->wantTo('Confirm the account with the correct code');

$I->fillField(['id' => 'code'], 'testCode');
$I->submitForm('#confirmAccountForm', []);
$I->see(mb_strtoupper('Zugang bestätigt'), 'h1');
