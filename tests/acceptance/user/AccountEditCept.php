<?php

/** @var \Codeception\Scenario $scenario */
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

$I->fillField('#userName', 'My new name');
$I->fillField('#userPwd', '123');
$I->submitForm('.userAccountForm', [], 'save');
$I->seeBootboxDialog('Das Passwort muss mindestens 4 Zeichen');
$I->acceptBootboxConfirm();

$I->fillField('#userPwd', '1234');
$I->submitForm('.userAccountForm', [], 'save');
$I->seeBootboxDialog('Die beiden Passwörter stimmen nicht überein');
$I->acceptBootboxConfirm();

$I->fillField('#userPwd2', '1234');
$I->checkOption('input[name=emailBlacklist]');
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
$I->fillField('#passwordInput', '1234');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');

$I->see('Willkommen!');
$I->click('#myAccountLink');

$I->seeInField('#userName', 'My new name');
$I->seeCheckboxIsChecked('input[name=emailBlacklist]');

$I->uncheckOption('input[name=emailBlacklist]');

$I->submitForm('.userAccountForm', [], 'save');
$I->see('Gespeichert.');

$I->dontSeeCheckboxIsChecked('input[name=emailBlacklist]');
