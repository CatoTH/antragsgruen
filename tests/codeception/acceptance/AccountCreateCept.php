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

/*
$I->cantSee('Voraussetzungen für einen Antrag');
$I->cantSee('JavaScript aktiviert sein');
$I->see('Überschrift', 'label');
$I->see('Antragstext', 'label');
$I->see('Begründung', 'label');

$I->seeCheckboxIsChecked("#personTypeNatural");
$I->cantSeeCheckboxIsChecked("#personTypeOrga");

if (method_exists($I, 'executeJS')) {
    $I->cantSee('Gremium, LAG...');
    $I->cantSee('Beschlussdatum');
    $I->selectOption('#personTypeOrga', \app\models\db\ISupporter::PERSON_ORGANIZATION);
    $I->canSee('Gremium, LAG...');
    $I->canSee('Beschlussdatum');
}

// Fill & Submit Form
$I->wantTo('Create a regular motion, but forgot the organization');
$I->fillField(['name' => 'title'], 'Testantrag 1');
if (method_exists($I, 'executeJS')) {
    $I->executeJS('CKEDITOR.instances.texts_1.setData("<p><strong>Test</strong></p>");');
    $I->executeJS('CKEDITOR.instances.texts_2.setData("<p><strong>Test 2</strong></p>");');
} else {
    $I->fillField(['name' => 'texts[1]'], 'Testantrag Text\n2');
    $I->fillField(['name' => 'texts[2]'], 'Testantrag Text\nBegründung');
}
$I->fillField(['name' => 'Initiator[name]'], 'Mein Name');
$I->fillField(['name' => 'Initiator[contactEmail]'], 'test@example.org');
$I->selectOption('#personTypeOrga', \app\models\db\ISupporter::PERSON_ORGANIZATION);
$I->submitForm('#motionCreateForm', [], 'create');

$I->see('No organization entered');

/*
// Fill & Submit Form
$I->wantTo('Create a regular motion');
$I->fillField(['name' => 'title'], 'Testantrag 1');
$I->fillField(['name' => 'texts[1]'], 'Testantrag Text\n2');
$I->fillField(['name' => 'texts[2]'], 'Testantrag Text\nBegründung');
$I->fillField(['name' => 'Initiator[name]'], 'Mein Name');
$I->fillField(['name' => 'Initiator[contactEmail]'], 'test@example.org');
$I->submitForm('#motionCreateForm', []);

$I->see('Bestätigen', 'h1');
*/
