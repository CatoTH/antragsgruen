<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('change my password and name');

$I->gotoConsultationHome();
$I->see('LOGIN', '#loginLink');
$I->click('#loginLink');

$I->see('LOGIN', 'h1');
$I->fillField('#username', 'testuser@example.org');
$I->fillField('#passwordInput', 'testuser');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');

$I->click('#myAccountLink');

$I->fillField('#nameGiven', 'My new');
$I->fillField('#nameFamily', 'name');
$I->fillField('#userPwd', '123');
$I->submitForm('.userAccountForm', [], 'save');
$I->seeBootboxDialog('Das Passwort muss mindestens 8 Zeichen');
$I->acceptBootboxConfirm();

$I->fillField('#userPwd', '12345678');
$I->submitForm('.userAccountForm', [], 'save');
$I->seeBootboxDialog('Die beiden Passwörter stimmen nicht überein');
$I->acceptBootboxConfirm();

$I->fillField('#userPwd2', '12345678');
$I->checkOption('input[name=emailBlocklist]');
$I->submitForm('.userAccountForm', [], 'save');
$I->see('Gespeichert.');

$I->logout();


$I->wantTo('check that the changes are saved');

$I->gotoConsultationHome();
$I->see('LOGIN', '#loginLink');
$I->click('#loginLink');

$I->see('LOGIN', 'h1');
$I->fillField('#username', 'testuser@example.org');
$I->fillField('#passwordInput', 'testuser');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');

$I->see('Falsches Passwort');
$I->fillField('#passwordInput', '12345678');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');

$I->see('Willkommen!');
$I->click('#myAccountLink');

$I->seeInField('#nameGiven', 'My new');
$I->seeInField('#nameFamily', 'name');
$I->seeCheckboxIsChecked('input[name=emailBlocklist]');

$I->uncheckOption('input[name=emailBlocklist]');

$I->submitForm('.userAccountForm', [], 'save');
$I->see('Gespeichert.');

$I->dontSeeCheckboxIsChecked('input[name=emailBlocklist]');
