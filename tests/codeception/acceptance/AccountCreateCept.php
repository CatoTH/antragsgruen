<?php

use tests\codeception\_pages\LoginPage;

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();



// Load Form

$I->wantTo('Load the login page');
LoginPage::openBy(
    $I,
    [
        'subdomain'        => 'stdparteitag',
        'consultationPath' => 'std-parteitag',
    ]
);
$I->see('Login', 'h1');

$I->fillField(['id' => 'username'], 'non_existant@example.org');
$I->fillField(['id' => 'password_input'], 'doesntmatter');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');
$I->see('BenutzerInnenname nicht gefunden.');

$I->cantSee('Passwort (Bestätigung):');
$I->checkOption(['id' => 'createAccount']);
$I->see('Passwort (Bestätigung):');

$I->fillField(['id' => 'username'], 'testaccount@example.org');
$I->fillField(['id' => 'password_input'], 'testpassword');
$I->fillField(['id' => 'passwordConfirm'], 'testpassword');
$I->fillField(['id' => 'name'], 'Tester');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');
$I->see('Willkommen!');
