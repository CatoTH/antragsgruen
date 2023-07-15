<?php

/** @var \Codeception\Scenario $scenario */
use app\models\db\ISupporter;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


// Load Form

$I->wantTo('motion Create site loads');
$I->gotoConsultationHome()->gotoMotionCreatePage();

$I->see('Antrag stellen', 'h1');
$I->seeInTitle('Antrag stellen');
$I->dontSee('Voraussetzungen für einen Antrag');
$I->see('Überschrift', 'label');
$I->see('Antragstext', 'label');
$I->see('Begründung', 'label');

$I->seeCheckboxIsChecked("#personTypeNatural");
$I->cantSeeCheckboxIsChecked("#personTypeOrga");

$I->dontSee('JavaScript aktiviert sein');
$I->see('Gremium, LAG...');
$I->dontSee('Beschlussdatum');
$I->dontSee('Ansprechperson');
$I->selectOption('#personTypeOrga', (string)ISupporter::PERSON_ORGANIZATION);
$I->dontSee('Gremium, LAG...');
$I->see('Beschlussdatum');
$I->see('Ansprechperson');
$I->seeElement('#section_holder_3 label.optional');
$I->seeElement('#section_holder_2 label.required');

// Fill & Submit Form
$I->wantTo('create a regular motion, but forgot the organization and resolution date');
$I->fillField(['name' => 'sections[1]'], 'Testantrag 1');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData("<p><strong>Test</strong></p>");');
$I->executeJS('CKEDITOR.instances.sections_3_wysiwyg.setData("<p><strong>Test 2</strong></p>");');
$I->fillField(['name' => 'Initiator[primaryName]'], 'Mein Name');
$I->fillField(['name' => 'Initiator[contactEmail]'], 'test@example.org');
$I->fillField(['name' => 'Initiator[contactPhone]'], '+49123456789');
$I->selectOption('#personTypeOrga', (string)ISupporter::PERSON_ORGANIZATION);
$I->submitForm('#motionEditForm', [], 'save');

$I->seeBootboxDialog('Es muss ein Beschlussdatum angegeben werden');
$I->acceptBootboxAlert();

$I->wantTo('finally create the motion for real');
$I->fillField(['name' => 'Initiator[resolutionDate]'], '12.01.2015');
$I->fillField(['name' => 'Initiator[contactName]'], 'MeinKontakt');
$I->submitForm('#motionEditForm', [], 'save');
$I->see(mb_strtoupper('Antrag bestätigen'), 'h1');


$I->wantTo('not confirm the motion, instead correcting a mistake');
$I->submitForm('#motionConfirmForm', [], 'modify');
$I->see(mb_strtoupper('Antrag stellen'), 'h1');


$I->wantTo('make some changes to the motion');
$I->fillField(['name' => 'sections[1]'], 'Testantrag 2');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData("<p><strong>Another string</strong></p>");');
$I->executeJS('CKEDITOR.instances.sections_3_wysiwyg.setData("<p><em>Italic is beautiful as well</em></p>");');
$I->executeJS('$("#initiatorPrimaryName").removeAttr("required");');
$I->fillField(['name' => 'Initiator[primaryName]'], '');
$I->fillField(['name' => 'Initiator[contactEmail]'], 'test2@example.org');
$I->fillField(['name' => 'Initiator[contactPhone]'], '+49-123-456789');
$I->selectOption('#personTypeOrga', (string)ISupporter::PERSON_ORGANIZATION);
$I->submitForm('#motionEditForm', [], 'save');

$I->wait(1);
$I->see('Bitte gib deinen Namen ein');
//$I->seeInField(['name' => 'Initiator[primaryName]'], ''); @TODO Decide if this makes sense
//$I->seeInField(['name' => 'Initiator[contactEmail]'], 'test2@example.org');
//$I->seeInField(['name' => 'Initiator[contactPhone]'], '+49-123-456789');
$I->dontSeeCheckboxIsChecked("#personTypeNatural");
$I->seeCheckboxIsChecked("#personTypeOrga");


$I->wantTo('finally submit the motion');
$I->fillField(['name' => 'Initiator[primaryName]'], 'My real name');
$I->fillField(['name' => 'Initiator[contactEmail]'], 'test2@example.org');
$I->fillField(['name' => 'Initiator[contactPhone]'], '+49-123-456789');
$I->submitForm('#motionEditForm', [], 'save');

$I->see(mb_strtoupper('Testantrag 2'), 'h1');
$I->see('Another string');
$I->see('Italic is beautiful as well');
$I->see('My real name');


$I->wantTo('confirm the submitted motion');
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->see(mb_strtoupper('Antrag veröffentlicht'), 'h1');
$I->see('Du hast den Antrag veröffentlicht. Er ist jetzt sofort sichtbar.');

$I->submitForm('#motionConfirmedForm', [], '');


$motionId     = AcceptanceTester::FIRST_FREE_MOTION_ID;
$motionPrefix = AcceptanceTester::FIRST_FREE_MOTION_TITLE_PREFIX;

$I->wantTo('check the visible data');
$I->see('Hallo auf Antragsgrün');
$I->see('Testantrag 2');
$I->click('.motionLink' . $motionId);
$I->see(mb_strtoupper($motionPrefix . ': Testantrag 2'), 'h1');

$I->see('My real name');
$I->dontSee('test2@example.org');
$I->dontSee('+49-123-456789');


$I->loginAsStdUser();
$I->see('My real name');
$I->dontSee('test2@example.org');
$I->dontSee('+49-123-456789');


$I->logout();
$I->loginAsStdAdmin();
$I->see('My real name');
$I->dontSee('test2@example.org');
$I->dontSee('+49-123-456789');
$I->click('.contactShow');
$I->see('test2@example.org');
$I->see('+49-123-456789');
