<?php

/** @var \Codeception\Scenario $scenario */
use app\models\db\ISupporter;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('make sure the supporter-warning appears for natural persons');

$page = $I->gotoConsultationHome(true, 'bdk', 'bdk');
$I->loginAsStdAdmin();
$I->click('#sidebar .createMotion');

$I->fillField(['name' => 'sections[20]'], 'Testantrag');
$I->executeJS('CKEDITOR.instances.sections_21_wysiwyg.setData("<p><strong>Test</strong></p>");');
$I->executeJS('CKEDITOR.instances.sections_22_wysiwyg.setData("<p><strong>Test 2</strong></p>");');
$I->fillField('#initiatorPrimaryName', 'Mein Name');
$I->fillField('#initiatorEmail', 'test@example.org');
$I->submitForm('#motionEditForm', [], 'save');

$I->seeBootboxDialog('Es müssen mindestens 19 Unterstützer*innen angegeben werden');
$I->acceptBootboxAlert();


$I->wantTo('make sure it does not appear for organizations');

$I->selectOption('#personTypeOrga', (string)ISupporter::PERSON_ORGANIZATION);
$I->fillField('#initiatorPrimaryName', 'Meine Organisation');
$I->submitForm('#motionEditForm', [], 'save');

$I->seeBootboxDialog('Es muss ein Beschlussdatum angegeben werden');
$I->acceptBootboxAlert();


$I->fillField('#resolutionDate', '01.01.2000');
$I->executeJS('$("[required]").removeAttr("required");');
$I->submitForm('#motionEditForm', [], 'save');

$I->dontSeeBootboxDialog('Es müssen mindestens 19 Unterstützer*innen angegeben werden');
$I->dontSee('Not enough supporters.');
$I->see(mb_strtoupper('Antrag bestätigen'), 'h1');
