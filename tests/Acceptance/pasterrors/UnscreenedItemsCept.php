<?php

/** @var \Codeception\Scenario $scenario */
use app\models\db\ISupporter;
use app\models\settings\Consultation;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('create a unscreened motion');

$I->gotoConsultationHome(true, 'bdk', 'bdk');
$I->loginAsStdAdmin();

$I->click('#sidebar .createMotion');

$title = 'Nicht freigeschalteter Testantrag';

$I->fillField(['name' => 'sections[20]'], $title);
$I->executeJS('CKEDITOR.instances.sections_21_wysiwyg.setData("<p><strong>Test</strong></p>");');
$I->executeJS('CKEDITOR.instances.sections_22_wysiwyg.setData("<p><strong>Test 2</strong></p>");');
$I->selectOption('#personTypeOrga', (string)ISupporter::PERSON_ORGANIZATION);
$I->fillField('#initiatorPrimaryName', 'Mein Name');
$I->fillField('#initiatorEmail', 'test@example.org');
$I->fillField('#resolutionDate', '01.01.2000');
$I->submitForm('#motionEditForm', [], 'save');
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->gotoConsultationHome(true, 'bdk', 'bdk');


$layoutTypes = Consultation::getStartLayouts();
foreach ($layoutTypes as $typeId => $typeName) {
    $I->wantTo('switch to: ' . $typeName);
    $I->click('#adminTodo');
    $I->see($title);
    $I->gotoStdAdminPage('bdk', 'bdk')->gotoAppearance();
    $I->selectOption('#startLayoutType', $typeId);
    $I->submitForm('#consultationAppearanceForm', [], 'save');

    $I->gotoConsultationHome(true, 'bdk', 'bdk');
    $I->dontSee($title);
}

// @TODO As an agenda item
