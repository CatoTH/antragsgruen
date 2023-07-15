<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->setAntragsgruenConfiguration(['confirmEmailAddresses' => false]);

$I->gotoConsultationHome();
$I->click('#loginLink');
$I->see('Login', 'h1');

$I->wantTo('Create an account');

$I->checkOption(['id' => 'createAccount']);
$I->see('Passwort (Bestätigung):');
$I->fillField(['id' => 'username'], 'testaccount@example.org');
$I->fillField(['id' => 'name'], 'Tester');
$I->fillField(['id' => 'passwordInput'], 'testpassword');
$I->fillField(['id' => 'passwordConfirm'], 'testpassword');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');

$I->dontSee(mb_strtoupper('Bestätige deinen Zugang'), 'h1');
$I->see('Test2', 'h1');
$I->see('Willkommen!', '.alert-success');



$I->wantTo('change the e-mail-address');
$I->click('#myAccountLink');
$I->click('.requestEmailChange');
$I->fillField('#userEmail', 'newmail@example.org');
$I->submitForm('.userAccountForm', [], 'save');
$I->see('newmail@example.org', '.currentEmail');
$I->dontSee('unbestätigt');
