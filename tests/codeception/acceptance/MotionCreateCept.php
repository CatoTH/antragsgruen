<?php

use tests\codeception\_pages\MotionCreatePage;

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();



// Load Form

$I->wantTo('Motion Create site loads');
MotionCreatePage::openBy(
    $I,
    [
        'subdomain'        => 'stdparteitag',
        'consultationPath' => 'std-parteitag',
    ]
);
$I->see('Antrag stellen', 'h1');
$I->seeInTitle('Antrag stellen');
$I->dontSee('Voraussetzungen für einen Antrag');
$I->see('Überschrift', 'label');
$I->see('Antragstext', 'label');
$I->see('Begründung', 'label');

$I->seeCheckboxIsChecked("#personTypeNatural");
$I->cantSeeCheckboxIsChecked("#personTypeOrga");

if (method_exists($I, 'executeJS')) {
    $I->dontSee('JavaScript aktiviert sein');
    $I->dontSee('Gremium, LAG...');
    $I->dontSee('Beschlussdatum');
    $I->selectOption('#personTypeOrga', \app\models\db\ISupporter::PERSON_ORGANIZATION);
    $I->see('Gremium, LAG...');
    $I->see('Beschlussdatum');
} else {
    $I->see('JavaScript aktiviert sein');
}

// Fill & Submit Form
$I->wantTo('Create a regular motion, but forgot the organization, motion type and resolution date');
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
$I->submitForm('#motionCreateForm', [], 'save');

$I->see('No organization entered');
$I->see('Motion Type not found');
$I->see('No resolution date entered');
$I->seeInField(['name' => 'Initiator[name]'], 'Mein Name');
$I->seeInField(['name' => 'Initiator[contactEmail]'], 'test@example.org');
$I->dontSeeCheckboxIsChecked("#personTypeNatural");
$I->seeCheckboxIsChecked("#personTypeOrga");
$I->see('Gremium, LAG...');
$I->see('Beschlussdatum');


// Fill & Submit Form
$I->wantTo('Create a regular motion, still forgot the resolution date');
$I->fillField(['name' => 'Initiator[organization]'], 'My company');
$I->selectOption('#motionType2', 2);

$I->submitForm('#motionCreateForm', [], 'save');

$I->dontSee('No organization entered');
$I->dontSee('Motion Type not found');
$I->see('No resolution date entered');



$I->wantTo('Finally create the motion for real');
$I->fillField(['name' => 'Initiator[resolutionDate]'], '12.01.2015');
$I->submitForm('#motionCreateForm', [], 'save');
$I->see(mb_strtoupper('Antrag bestätigen'), 'h1');


$I->wantTo('Not confirm the motion, instead correcting a mistake');
$I->submitForm('#motionConfirmForm', [], 'modify');
$I->see(mb_strtoupper('Antrag stellen'), 'h1');
